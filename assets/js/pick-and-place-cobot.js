(function () {
   document.querySelectorAll('.pp-faq__list').forEach(function (list) {
      const questions = list.querySelectorAll('details');

      questions.forEach(function (question) {
         question.addEventListener('toggle', function () {
            if (!question.open) return;

            questions.forEach(function (otherQuestion) {
               if (otherQuestion !== question) otherQuestion.open = false;
            });
         });
      });
   });
}());
