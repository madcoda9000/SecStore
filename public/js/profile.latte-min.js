function generatePassword(){let e="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*()-_",t="";for(let n=0;n<16;n++)t+=e.charAt(Math.floor(Math.random()*e.length));return t}function switch2fa(e){faSwitch=document.getElementById("mfaSwitch");const t=new bootstrap.Modal(document.getElementById("2faModale"));faSwitch&&(fetchAction="/disable-2fa",!0===faSwitch.checked&&(fetchAction="/enable-2fa"),fetch(fetchAction,{method:"POST"}).then((e=>e.json())).then((e=>{!0===e.success&&"/disable-2fa"==fetchAction?(document.getElementById("2faModaleText").innerText="2FA Authentication disabled successfully!",t.show()):!0===e.success&&"/enable-2fa"==fetchAction&&(document.getElementById("2faModaleText").innerText="2FA Authentication enabled successfully!",t.show())})))}document.getElementById("togglePassword").addEventListener("click",(function(){let e=document.getElementById("new_password"),t=document.getElementById("eyeicon");"password"===e.type?(e.type="text",t.classList.remove("bi-eye"),t.classList.remove("text-success"),t.classList.add("bi-eye-slash"),t.classList.add("text-danger")):(e.type="password",t.classList.remove("bi-eye-slash"),t.classList.remove("text-danger"),t.classList.add("bi-eye"),t.classList.add("text-success"))})),document.getElementById("generatePassword").addEventListener("click",(function(){let e=document.getElementById("new_password"),t=generatePassword();e.value=t})),document.addEventListener("DOMContentLoaded",(function(){const e=new bootstrap.Modal(document.getElementById("2faModal"));enable2faBtn=document.getElementById("enable2fabutton"),enable2faBtn&&enable2faBtn.addEventListener("click",(function(){e.show()}))})),document.getElementById("setEnable2faBtn").addEventListener("click",(function(){fetch("/initiate2faSetup",{method:"POST"}).then((e=>e.json())).then((e=>{e.success&&(window.location.href="/logout")}))}));