(() => {
 const dialog=document.querySelector('#owner-delete-dialog');if(!dialog)return;
 let opener=null;
 const open=source=>{opener=source||document.querySelector('[data-user-delete-open]');if(!dialog.open)dialog.showModal();dialog.querySelector('[data-user-delete-focus]')?.focus();};
 document.querySelectorAll('[data-user-delete-open]').forEach(button=>button.addEventListener('click',()=>open(button)));
 dialog.querySelectorAll('[data-user-delete-close]').forEach(button=>button.addEventListener('click',()=>dialog.close()));
 dialog.addEventListener('close',()=>{if(!document.querySelector('dialog[open]'))opener?.focus();});
 dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const box=dialog.getBoundingClientRect();if(event.clientX<box.left||event.clientX>box.right||event.clientY<box.top||event.clientY>box.bottom)dialog.close();});
 dialog.querySelector('[data-user-delete-form]')?.addEventListener('submit',event=>{event.currentTarget.dataset.saved='1';});
 if(dialog.dataset.deleteReopen==='1')open();
})();
