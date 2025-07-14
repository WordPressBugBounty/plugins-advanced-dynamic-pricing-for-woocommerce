jQuery(document).ready(function($) {
  function initVariationForm($form) {
    if ($form.length) {
      $form.wc_variation_form();
      $form.trigger('check_variations');
    }
  }

  $('.variations_form').each(function() {
    initVariationForm($(this));
  });

  $(document).on('change', '.variations_form .variations select', function() {
    const $form = $(this).closest('.variations_form');
    initVariationForm($form);
  });

  $(document).on('found_variation', '.variations_form', function(event, variation) {
    const $form = $(this);
    $form.find('.variation_id').val(variation.variation_id);
  });

  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(mutation => {
      $(mutation.addedNodes).each(function() {
        const $node = $(this);

        if ($node.hasClass('variations_form')) {
          initVariationForm($node);
        } else {
          $node.find('.variations_form').each(function() {
            initVariationForm($(this));
          });
        }
      });
    });
  });

  const target = document.body;
  if (target) {
    observer.observe(target, {
      childList: true,
      subtree: true
    });
  }
});
