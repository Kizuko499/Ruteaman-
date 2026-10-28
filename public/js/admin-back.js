(() => {
 document.querySelectorAll('[data-admin-back]').forEach(link => {
  // Native anchor fallback works without JavaScript or a previous console page.
  let previous=null;
  try{
   const ref=new URL(document.referrer),current=new URL(location.href);
   const adminPage=ref.pathname==='/admin'||ref.pathname.startsWith('/admin/');
   const differentPage=ref.pathname+ref.search!==current.pathname+current.search;
   if(ref.origin===current.origin&&adminPage&&differentPage&&window.history.length>1)previous=ref;
  }catch{/* Empty, malformed or external referrers retain the Ringkasan fallback. */}
  if(!previous)return;
  link.href=previous.href;
  link.addEventListener('click',event=>{
   if(event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
   event.preventDefault();
   window.history.back();
  });
 });
})();
