(() => {
  'use strict';
  const messages = {
    accepted:'Solicitud procesada. El mensaje fue aceptado por el transporte; esto no confirma su recepción en el buzón.',
    stored:'El documento ha quedado registrado de forma privada.',
    invalid:'Revisa los campos indicados.', conflict:'La referencia ya se usó con otros datos. No se ha realizado otro envío.',
    rate_limited:'Se alcanzó el límite de solicitudes. Inténtalo más tarde.',
    processing:'Esta referencia ya se está procesando. No vuelvas a enviarla.',
    rejected:'El transporte no aceptó el mensaje. No se ha reintentado automáticamente.',
    unknown:'No se pudo confirmar el resultado. Conserva la referencia y no repitas el envío automáticamente.',
    disabled:'Canal deshabilitado. No introduzcas datos personales.',
    invalid_request:'No se pudo verificar la solicitud. Recarga el formulario antes de intentarlo de nuevo.',
    file_rejected:'No se admite el documento adjunto.'
  };
  document.querySelectorAll('[data-arv-form]').forEach(form => {
    let busy = false;
    const prefix = form.dataset.prefix;
    const section = form.closest('.arv-form');
    const result = section.querySelector('.arv-result');
    const button = form.querySelector('button[type=submit]');
    function show(data) {
      result.replaceChildren();
      result.hidden = false;
      result.setAttribute('role', Object.keys(data.errors || {}).length ? 'alert':'status');
      const p = document.createElement('p');
      p.textContent = (form.dataset.lab==='1'?'PRUEBA AISLADA, SIN CORREO REAL. ':'') + (messages[data.state] || messages.unknown);
      result.append(p);
      if (/^[a-f0-9]{64}$/.test(data.reference || '')) { const ref = document.createElement('p');ref.textContent='Referencia: '+data.reference;result.append(ref); }
      form.querySelectorAll('[aria-invalid]').forEach(el => {el.removeAttribute('aria-invalid');el.removeAttribute('aria-errormessage');});
      section.querySelectorAll('.arv-field-error').forEach(el => el.remove());
      const list = document.createElement('ul');
      Object.entries(data.errors || {}).forEach(([key, text]) => {
        const li = document.createElement('li');
        const field = form.elements.namedItem(key);
        if (field instanceof HTMLElement && field.id) {
          const a=document.createElement('a');a.href='#'+field.id;a.textContent=text;a.addEventListener('click',()=>field.focus());li.append(a);
          const detail=document.createElement('p');detail.className='arv-field-error';detail.id=prefix+'-'+key+'-error';detail.textContent=text;field.after(detail);
          field.setAttribute('aria-invalid','true');field.setAttribute('aria-errormessage',detail.id);
        } else li.textContent=text;
        list.append(li);
      });
      if (list.childElementCount) result.append(list);
      result.focus();
    }
    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (busy || form.querySelector('fieldset').disabled) return;
      const body = new FormData(form); // Snapshot before disabling anything.
      busy=true;button.disabled=true;form.setAttribute('aria-busy','true');
      try {
        const response=await fetch(form.action,{method:'POST',credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'},body});
        const data=await response.json();
        show(data);
        if (data.state==='file_rejected' && typeof data.next_csrf==='string') form.elements.namedItem('csrf').value=data.next_csrf;
        if (['accepted','stored','processing','rejected','unknown'].includes(data.state)) form.querySelector('fieldset').disabled=true;
      } catch (error) {
        show({state:'unknown',errors:{}});
        // The request may have reached the server: do not auto-retry or rotate the token.
        form.querySelector('fieldset').disabled=true;
      } finally {busy=false;form.removeAttribute('aria-busy');if(!form.querySelector('fieldset').disabled)button.disabled=false;}
    });
  });
})();
