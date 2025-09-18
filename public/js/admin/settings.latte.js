
document.getElementById("togglePassword").addEventListener("click", function () {
  let passwordField = document.getElementById("mailpw");
  let eyeIcon = document.getElementById("eyeIcon");

  if (passwordField.type === "password") {
    passwordField.type = "text";
    eyeIcon.classList.remove("bi-eye");
    eyeIcon.classList.remove("text-success");
    eyeIcon.classList.add("bi-eye-slash");
    eyeIcon.classList.add("text-danger");    
  } else {
    passwordField.type = "password";
    eyeIcon.classList.remove("bi-eye-slash");
    eyeIcon.classList.remove("text-danger");
    eyeIcon.classList.add("bi-eye");
    eyeIcon.classList.add("text-success");
  }
});
