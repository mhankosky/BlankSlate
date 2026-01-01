function applyTheme(t){document.documentElement.setAttribute('data-theme',t);localStorage.setItem('theme',t);const b=document.getElementById('theme-toggle-btn');if(b)b.innerText=(t==='dark'?'☀️':'🌙')}
function toggleTheme(){applyTheme(localStorage.getItem('theme')==='dark'?'light':'dark')}
document.addEventListener('DOMContentLoaded',()=>applyTheme(localStorage.getItem('theme')||'light'));