document.querySelectorAll('.palletizing-faq__list').forEach((list) => {
   const questions = list.querySelectorAll('details');

   questions.forEach((question) => {
      question.addEventListener('toggle', () => {
         if (!question.open) return;

         questions.forEach((otherQuestion) => {
            if (otherQuestion !== question) otherQuestion.open = false;
         });
      });
   });
});
