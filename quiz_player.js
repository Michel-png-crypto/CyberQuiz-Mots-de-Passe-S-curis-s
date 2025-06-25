// js/quiz_player.js

document.addEventListener('DOMContentLoaded', () => {
    // Récupération des éléments du DOM
    const questionArea = document.getElementById('question-area');
    const questionTextEl = document.getElementById('question-text');
    const optionsContainerEl = document.getElementById('options-container');
    const nextBtn = document.getElementById('next-btn');
    const stopBtn = document.getElementById('stop-btn');
    const timeEl = document.getElementById('time');
    const quizForm = document.getElementById('quiz-form');

    let currentQuestionIndex = 0;
    let userAnswers = [];
    let timerInterval;

    // Si le quiz est vide, on affiche un message et on arrête tout.
    if (!quizData || quizData.length === 0) {
        questionArea.style.display = 'block';
        questionTextEl.textContent = "Ce quiz ne contient aucune question pour le moment.";
        document.querySelector('.quiz-navigation').style.display = 'none'; // Cache les boutons
        document.getElementById('timer').style.display = 'none'; // Cache le minuteur
        return;
    }

    // Fonction principale qui démarre le quiz
    function startQuiz() {
        questionArea.style.display = 'block';
        startTimer(quizDuration * 60);
        showQuestion(currentQuestionIndex);
        nextBtn.addEventListener('click', handleNextQuestion);
        stopBtn.addEventListener('click', handleStopQuiz);
    }
    
    // Gère le clic sur "Arrêter le Quiz"
    function handleStopQuiz() {
        const confirmStop = confirm("Êtes-vous sûr de vouloir abandonner ? Votre score sera calculé sur la base des réponses déjà données.");
        if (confirmStop) {
            endQuiz('abandoned');
        }
    }

    // === CETTE FONCTION ÉTAIT VIDE ===
    // Affiche la question et ses options de réponse
    function showQuestion(index) {
        nextBtn.disabled = true;
        optionsContainerEl.innerHTML = '';

        const question = quizData[index];
        questionTextEl.textContent = `${index + 1}. ${question.text}`;

        if (question.type === 'multiple-choice') {
            const alphaIndex = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            question.options.forEach((option, i) => {
                optionsContainerEl.innerHTML += `
                    <div class="option">
                        <input type="radio" id="option${i}" name="answer" value="${alphaIndex[i]}">
                        <label for="option${i}">${alphaIndex[i]}) ${option}</label>
                    </div>`;
            });
        } else if (question.type === 'true-false') {
            optionsContainerEl.innerHTML += `
                <div class="option"><input type="radio" id="option_true" name="answer" value="Vrai"><label for="option_true">Vrai</label></div>
                <div class="option"><input type="radio" id="option_false" name="answer" value="Faux"><label for="option_false">Faux</label></div>`;
        } else if (question.type === 'text-answer') {
            optionsContainerEl.innerHTML = `<input type="text" id="text_answer" name="answer" class="form-control" placeholder="Entrez votre réponse">`;
        }
        
        optionsContainerEl.addEventListener('input', () => { nextBtn.disabled = false; });

        if (currentQuestionIndex === quizData.length - 1) {
            nextBtn.textContent = 'Terminer le Quiz';
        }
    }

    // === CETTE FONCTION ÉTAIT VIDE ===
    // Gère le clic sur "Question Suivante"
    function handleNextQuestion() {
        saveUserAnswer();
        currentQuestionIndex++;
        if (currentQuestionIndex < quizData.length) {
            showQuestion(currentQuestionIndex);
        } else {
            endQuiz('completed');
        }
    }

    // === CETTE FONCTION ÉTAIT VIDE ===
    // Sauvegarde la réponse de l'utilisateur dans le tableau `userAnswers`
    function saveUserAnswer() {
        const question = quizData[currentQuestionIndex];
        let selectedValue = '';
        const radioChecked = document.querySelector('input[name="answer"]:checked');
        const textInput = document.getElementById('text_answer');

        if (radioChecked) {
            selectedValue = radioChecked.value;
        } else if (textInput) {
            selectedValue = textInput.value.trim();
        }
        userAnswers.push({ question_id: question.question_id, answer: selectedValue });
    }

    // === CETTE FONCTION ÉTAIT VIDE ===
    // Démarre et gère le minuteur
    function startTimer(durationInSeconds) {
        let timer = durationInSeconds;
        timerInterval = setInterval(() => {
            let minutes = Math.floor(timer / 60);
            let seconds = timer % 60;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            timeEl.textContent = `${minutes}:${seconds}`;

            if (--timer < 0) {
                endQuiz('time_out');
            }
        }, 1000);
    }

    // Termine le quiz et soumet les réponses
    function endQuiz(reason) {
        clearInterval(timerInterval);
        
        if (reason === 'time_out') {
            alert("Le temps est écoulé ! Le quiz va être soumis.");
            saveUserAnswer();
        }
        
        const answersInput = document.createElement('input');
        answersInput.type = 'hidden';
        answersInput.name = 'answers';
        answersInput.value = JSON.stringify(userAnswers);
        quizForm.appendChild(answersInput);
        quizForm.submit();
    }

    // Lancement du quiz !
    startQuiz();
});