document.querySelectorAll('.welding-cobot__faq-list').forEach((accordion) => {
   const items = accordion.querySelectorAll('details');

   items.forEach((item) => {
      item.addEventListener('toggle', () => {
         if (!item.open) return;

         items.forEach((otherItem) => {
            if (otherItem !== item) otherItem.open = false;
         });
      });
   });
});
