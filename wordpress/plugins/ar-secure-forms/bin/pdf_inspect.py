#!/usr/bin/env python3
"""Bounded structural/policy check. No network, rendering, shell or antivirus claim."""
import io, json, logging, os, sys, warnings


def inspect(path, max_bytes):
    import pypdf
    from pypdf import PdfReader, Configuration, apply_configuration
    from pypdf.generic import DictionaryObject, ArrayObject, IndirectObject, StreamObject

    if tuple(int(x) for x in pypdf.__version__.split('.')[:3]) < (6, 21, 2):
        raise ValueError('dependency_version')
    if os.path.islink(path) or not 0 < os.path.getsize(path) <= max_bytes:
        raise ValueError('size')
    capture = io.StringIO()
    handler = logging.StreamHandler(capture)
    logger = logging.getLogger('pypdf')
    logger.handlers = [handler]
    logger.propagate = False
    limits = Configuration(
        maximum_declared_stream_length=16_000_000,
        zlib_maximum_output_length=16_000_000,
        array_based_stream_maximum_output_length=16_000_000,
        run_length_maximum_output_length=16_000_000,
        lzw_maximum_output_length=16_000_000,
        jbig2_maximum_output_length=16_000_000,
        array_maximum_element_count=20_000,
        dictionary_maximum_element_count=20_000,
        page_tree_maximum_entries=1000,
        page_tree_maximum_depth=32,
        stream_parsing_work_maximum_count=100_000,
        stream_decoding_work_maximum_length=32_000_000,
    )
    banned_keys = {'/JavaScript','/JS','/OpenAction','/AA','/EmbeddedFiles','/EF',
                   '/XFA','/AcroForm','/RichMedia','/RichMediaContent','/Collection'}
    banned_actions = {'/JavaScript','/Launch','/SubmitForm','/ImportData','/GoToR',
                      '/GoToE','/Rendition','/Movie','/Sound','/SetOCGState'}
    seen_refs = set()
    visited = 0
    decoded = 0

    def walk(obj, depth=0):
        nonlocal visited, decoded
        visited += 1
        if visited > 50_000 or depth > 48:
            raise ValueError('complexity')
        if isinstance(obj, IndirectObject):
            key = (obj.idnum, obj.generation)
            if key in seen_refs:
                return
            seen_refs.add(key)
            walk(obj.get_object(), depth + 1)
        elif isinstance(obj, DictionaryObject):
            keys = set(str(k) for k in obj.keys())
            if keys & banned_keys or str(obj.get('/S','')) in banned_actions:
                raise ValueError('active_content')
            if str(obj.get('/Type','')) in {'/EmbeddedFile','/Filespec'}:
                raise ValueError('embedded_file')
            if '/URI' in obj and not str(obj['/URI']).lower().startswith(('https://','http://')):
                raise ValueError('uri_scheme')
            for value in obj.values():
                walk(value, depth + 1)
            if isinstance(obj, StreamObject):
                data = obj.get_data()
                decoded += len(data)
                if decoded > 32_000_000:
                    raise ValueError('decoded_size')
        elif isinstance(obj, ArrayObject):
            for value in obj:
                walk(value, depth + 1)

    with warnings.catch_warnings(record=True) as caught, apply_configuration(limits):
        warnings.simplefilter('always')
        reader = PdfReader(path, strict=True)
        if reader.is_encrypted or '/Encrypt' in reader.trailer:
            raise ValueError('encrypted')
        pages = len(reader.pages)
        if not 1 <= pages <= 100:
            raise ValueError('pages')
        entries = [(g, n) for g, group in reader.xref.items() for n in group if n]
        entries += [(0, n) for n in reader.xref_objStm]
        if len(entries) > 10_000:
            raise ValueError('objects')
        walk(reader.trailer)
        # Also inspect orphan/indexed objects and decoded object streams.
        for generation, number in entries:
            walk(IndirectObject(number, generation, reader))
        if caught or capture.getvalue():
            raise ValueError('parser_warning')
    return {'ok': True, 'pages': pages, 'parser': pypdf.__version__}


if __name__ == '__main__':
    try:
        # The PHP parent also enforces wall time/output size. These are local process limits.
        import resource
        def lower_limit(which, desired):
            _, hard = resource.getrlimit(which)
            cap = desired if hard == resource.RLIM_INFINITY else min(desired, hard)
            resource.setrlimit(which, (cap, cap))
        lower_limit(resource.RLIMIT_CPU, 5)
        # macOS rejects RLIMIT_AS changes; DATA plus parser bounds are used there.
        # Native integration must certify the worker/container memory limit.
        if sys.platform != 'darwin':
            lower_limit(resource.RLIMIT_AS, 512 * 1024 * 1024)
        # Darwin has no usable per-process address-space limit here. The parent
        # wall-time and parser bounds still apply in the synthetic lab; production
        # activation requires a certified external memory/process sandbox.
        result = inspect(sys.argv[1], int(sys.argv[2]))
        print(json.dumps(result))
    except BaseException:
        print(json.dumps({'ok': False}))
        sys.exit(2)
