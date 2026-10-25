import fs from 'node:fs';
import path from 'node:path';
const dep='/Users/emmanuelbarrios/Documents/Codex/american-recruiters-release-20261023/tools/release/node_modules/';
const {PHP}=await import(dep+'@php-wasm/universal/index.js');
const {loadNodeRuntime,createNodeFsMountHandler}=await import(dep+'@php-wasm/node/index.js');
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:1}}));
await php.mount('/theme',createNodeFsMountHandler(path.resolve('wordpress/ar-editorial')));
const result=await php.run({code:`<?php $errors=[];$files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/theme'));foreach($files as $f){if($f->getExtension()!=='php')continue;try{token_get_all(file_get_contents($f->getPathname()),TOKEN_PARSE);}catch(Throwable $e){$errors[]=$f->getPathname().': '.$e->getMessage();}}echo json_encode(['syntax_errors'=>$errors,'DOMDocument'=>class_exists('DOMDocument'),'mbstring'=>function_exists('mb_strtolower')]);`});
console.log(result.text,result.errors);process.exit(result.exitCode);
