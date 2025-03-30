document.addEventListener("DOMContentLoaded",(function(){document.getElementById("search").addEventListener("input",(function(){fetchLogs()})),document.getElementById("pageSize").addEventListener("change",(function(){fetchLogs()}))}));let currentPage=1;function fetchLogs(){let e=document.getElementById("search").value,n=document.getElementById("pageSize").value,t=document.getElementById("loadingSpinner"),a=document.getElementById("logTableBody"),i=document.getElementById("logCardsBody");t.style.display="block",a.innerHTML="",i.innerHTML="",fetch(`/admin/logs/fetchErrorlogs?search=${e}&pageSize=${n}&page=${currentPage}`).then((e=>e.json())).then((e=>{a.innerHTML="",i.innerHTML="",e.logs.forEach((e=>{let n=`<tr>\n                    <td>${e.id}</td>\n                    <td>${e.type}</td>\n                    <td>${e.datum_zeit}</td>\n                    <td>${e.user}</td>\n                    <td>${e.context}</td>\n                    <td>${e.message}</td>\n                </tr>`;a.innerHTML+=n;let t=`<div class="card mb-2 p-2">\n                    <div><strong>#${e.id}</strong></div>\n                    <div><strong>Type:</strong> ${e.type}</div>\n                    <div><strong>Date:</strong> ${e.datum_zeit}</div>\n                    <div><strong>User:</strong> ${e.user}</div>\n                    <div><strong>Context:</strong> ${e.context}</div>\n                    <div><strong>Message:</strong> ${e.message}</div>\n                </div>`;i.innerHTML+=t})),updatePagination(e.page,e.totalPages)})).catch((e=>{})).finally((()=>{t.style.display="none"}))}function updatePagination(e,n){let t=document.getElementById("pagination"),a=document.getElementById("paginationInfo");t.innerHTML="",a.textContent=`Seite ${e} von ${n}`;let i=Math.max(1,e-Math.floor(2.5)),l=Math.min(n,i+5-1);if(e>1){let n=document.createElement("li");n.className="page-item",n.innerHTML=`<a class="page-link" href="#" onclick="goToPage(${e-1})">&laquo;</a>`,t.appendChild(n)}if(i>1){let e=document.createElement("li");e.className="page-item",e.innerHTML='<a class="page-link" href="#" onclick="goToPage(1)">1</a>',t.appendChild(e),i>2&&(t.innerHTML+='<li class="page-item disabled"><span class="page-link">...</span></li>')}for(let n=i;n<=l;n++){let a=document.createElement("li");a.className="page-item "+(n===e?"active":""),a.innerHTML=`<a class="page-link" href="#" onclick="goToPage(${n})">${n}</a>`,t.appendChild(a)}if(l<n){l<n-1&&(t.innerHTML+='<li class="page-item disabled"><span class="page-link">...</span></li>');let e=document.createElement("li");e.className="page-item",e.innerHTML=`<a class="page-link" href="#" onclick="goToPage(${n})">${n}</a>`,t.appendChild(e)}if(e<n){let n=document.createElement("li");n.className="page-item",n.innerHTML=`<a class="page-link" href="#" onclick="goToPage(${e+1})">&raquo;</a>`,t.appendChild(n)}}function goToPage(e){currentPage=e,fetchLogs()}