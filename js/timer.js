let timeLeft = 20;
const timerElement = document.getElementById("timer");
const submitButton = document.getElementById("submitBtn");

let countdown = setInterval(() => {
  timeLeft--;
  timerElement.textContent = `Temps restant : ${timeLeft}s`;

  if (timeLeft <= 0) {
    clearInterval(countdown);
    timerElement.textContent = "Temps écoulé !";
    submitButton.disabled = true;

    const message = document.createElement("p");
    message.textContent = " Le temps est terminé. Vous ne pouvez plus répondre.";
    message.style.color = "red";
    message.style.marginTop = "20px";
    message.style.fontWeight = "bold";
    document.querySelector(".container").appendChild(message);
  }
}, 1000);

submitButton.addEventListener("click", function () {
  const q1 = document.querySelector('input[name="q1"]:checked');

  // Supprimer anciens messages
  const oldMessages = document.querySelectorAll(".msg");
  oldMessages.forEach(msg => msg.remove());

  if (!q1) {
    const error = document.createElement("p");
    error.textContent = " Veuillez répondre à la question 1 avant de soumettre.";
    error.classList.add("msg");
    error.style.color = "red";
    error.style.marginTop = "20px";
    error.style.fontWeight = "bold";
    document.querySelector(".container").appendChild(error);
    return;
  }

  const success = document.createElement("p");
  success.textContent = " Merci ! Votre quiz a été soumis avec succès.";
  success.classList.add("msg");
  success.style.color = "blue";
  success.style.marginTop = "20px";
  success.style.fontWeight = "bold";
  document.querySelector(".container").appendChild(success);

  submitButton.disabled = true;
});
