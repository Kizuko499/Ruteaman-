
export function initMenu(){
 const dialog=document.querySelector('#account-menu');if(!dialog)return;
 let trigger=null,previousOverflow='';
 const close=()=>{if(dialog.open)dialog.close();};
 document.querySelectorAll('[data-account-open]').forEach(button=>button.addEventListener('click',()=>{trigger=button;button.setAttribute('aria-expanded','true');previousOverflow=document.documentElement.style.overflow;document.documentElement.style.overflow='hidden';dialog.showModal();dialog.querySelector('[data-account-close]').focus();}));
 dialog.querySelector('[data-account-close]').addEventListener('click',close);
 dialog.addEventListener('close',()=>{document.documentElement.style.overflow=previousOverflow;document.querySelectorAll('[data-account-open]').forEach(b=>b.setAttribute('aria-expanded','false'));trigger?.focus({preventScroll:true});});
 dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)close();});
 dialog.addEventListener('keydown',event=>{if(event.key==='Escape'){event.preventDefault();close();}if(event.key!=='Tab')return;const focusable=[...dialog.querySelectorAll('button,a[href],input,select')].filter(e=>!e.disabled&&e.getClientRects().length);const first=focusable[0],last=focusable.at(-1);if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}});
 let start=null;const grip=dialog.querySelector('[data-sheet-grip]');grip.addEventListener('pointerdown',e=>{if(innerWidth>=1024)return;start=e.clientY;grip.setPointerCapture(e.pointerId);});grip.addEventListener('pointerup',e=>{if(start!==null&&e.clientY-start>65)close();start=null;});grip.addEventListener('pointercancel',()=>start=null);
 const viewport=window.visualViewport;const updateKeyboard=()=>{const editing=document.activeElement?.matches('input,textarea,select');document.body.classList.toggle('keyboard-open',Boolean(editing&&viewport&&innerHeight-viewport.height>140));};viewport?.addEventListener('resize',updateKeyboard);document.addEventListener('focusin',updateKeyboard);document.addEventListener('focusout',()=>setTimeout(updateKeyboard,100));
}
