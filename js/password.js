
const uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
const lowercase = "abcdefghijklmnopqrstuvwxyz";
const numbers = "0123456789";
const symbols = "!@#$%^&*()-_=+[]{}|;:,.<>?";


function generatePassword() {
  const length = document.getElementById("length").value;
  const useUppercase = document.getElementById("uppercase").checked;
  const useLowercase = document.getElementById("lowercase").checked;
  const useNumbers = document.getElementById("numbers").checked;
  const useSymbols = document.getElementById("symbols").checked;

  let characters = "";
  if (useUppercase) characters += uppercase;
  if (useLowercase) characters += lowercase;
  if (useNumbers) characters += numbers;
  if (useSymbols) characters += symbols;

  let password = "";
  if (characters.length === 0) {
    alert("Veuillez sélectionner au moins une option !");
    return;
  }

  for (let i = 0; i < length; i++) {
    const index = Math.floor(Math.random() * characters.length);
    password += characters[index];
  }

  document.getElementById("password").textContent = password;
  updateStrength(password);
}


function copyPassword() {
  const password = document.getElementById("password").textContent;
  navigator.clipboard.writeText(password)
    .then(() => alert("Mot de passe copié !"))
    .catch(err => alert("Erreur de copie : " + err));
}


function updateStrength(password) {
  let score = 0;
  if (/[A-Z]/.test(password)) score++;
  if (/[a-z]/.test(password)) score++;
  if (/[0-9]/.test(password)) score++;
  if (/[^A-Za-z0-9]/.test(password)) score++;
  if (password.length >= 12) score++;

  const strength = document.getElementById("strength");
  if (score <= 2) {
    strength.textContent = "Force : Faible ";
    strength.style.color = "red";
  } else if (score === 3 || score === 4) {
    strength.textContent = "Force : Moyenne ";
    strength.style.color = "orange";
  } else {
    strength.textContent = "Force : Forte ";
    strength.style.color = "green";
  }
}
