document.addEventListener("DOMContentLoaded",(function(){document.getElementById("search").addEventListener("input",(function(){fetchRoles()})),document.getElementById("pageSize").addEventListener("change",(function(){fetchRoles()}))}));let currentPage=1;function fetchRoles(){let e=document.getElementById("search").value,t=document.getElementById("pageSize").value,n=document.getElementById("loadingSpinner"),a=document.getElementById("rolesTableBody");n.style.display="block",a.innerHTML="",fetch(`/admin/roles?search=${e}&pageSize=${t}&page=${currentPage}`).then((e=>e.json())).then((e=>{a.innerHTML="",e.roles.forEach((e=>{let t=`<tr>\n                    <td>${e.id}</td>\n                    <td>${e.roleName}</td>\n                    <td>\n                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(${e.id}, '${e.roleName}')">Löschen</button>\n                    </td>\n                </tr>`;a.innerHTML+=t})),updatePagination(e.page,e.totalPages)})).catch((e=>{})).finally((()=>{n.style.display="none"}))}function updatePagination(e,t){let n=document.getElementById("pagination"),a=document.getElementById("paginationInfo");n.innerHTML="",a.textContent=`Seite ${e} von ${t}`;let l=Math.max(1,e-Math.floor(2.5)),o=Math.min(t,l+5-1);if(e>1){let t=document.createElement("li");t.className="page-item",t.innerHTML=`<a class="page-link" href="#" onclick="goToPage(${e-1})">&laquo;</a>`,n.appendChild(t)}if(l>1){let e=document.createElement("li");e.className="page-item",e.innerHTML='<a class="page-link" href="#" onclick="goToPage(1)">1</a>',n.appendChild(e),l>2&&(n.innerHTML+='<li class="page-item disabled"><span class="page-link">...</span></li>')}for(let t=l;t<=o;t++){let a=document.createElement("li");a.className="page-item "+(t===e?"active":""),a.innerHTML=`<a class="page-link" href="#" onclick="goToPage(${t})">${t}</a>`,n.appendChild(a)}if(o<t){o<t-1&&(n.innerHTML+='<li class="page-item disabled"><span class="page-link">...</span></li>');let e=document.createElement("li");e.className="page-item",e.innerHTML=`<a class="page-link" href="#" onclick="goToPage(${t})">${t}</a>`,n.appendChild(e)}if(e<t){let t=document.createElement("li");t.className="page-item",t.innerHTML=`<a class="page-link" href="#" onclick="goToPage(${e+1})">&raquo;</a>`,n.appendChild(t)}}function goToPage(e){currentPage=e,fetchRoles()}function addRole(){let e=document.getElementById("newRoleName").value,t=new FormData;t.append("roleName",e),fetch("/admin/roles/add",{method:"POST",body:t}).then((()=>{fetchRoles(),document.getElementById("newRoleName").value="",bootstrap.Modal.getInstance(document.getElementById("addRoleModal")).hide(),showToast(`Rolle "${e}" erfolgreich erstellt!`,"success","Success")}))}function confirmDelete(e,t){fetch(`/admin/roles/checkUsers?role=${t}`).then((e=>e.json())).then((n=>{n.inUse?showToast(`Rolle "${t}" kann nicht gelöscht werden, da sie noch Benutzern zugewiesen ist!`,"warning","Warning"):(document.getElementById("deleteMessage").textContent=`Soll die Rolle "${t}" wirklich gelöscht werden?`,document.getElementById("deleteConfirmBtn").onclick=function(){deleteRole(e)},new bootstrap.Modal(document.getElementById("confirmDeleteModal")).show())}))}function deleteRole(e){let t=document.getElementById("deleteMessage").textContent.match(/"([^"]+)"/)[1],n=new FormData;n.append("roleId",e),fetch("/admin/roles/delete",{method:"POST",body:n}).then((()=>{fetchRoles(),bootstrap.Modal.getInstance(document.getElementById("confirmDeleteModal")).hide(),showToast(`Rolle "${t}" erfolgreich gelöscht!`,"success","Success")}))}