(function ($) {
  'use strict';

  function initRelatedProjectsSliders(context) {
    if (typeof $.fn.slick !== 'function') {
      return;
    }

    var $root = context ? $(context) : $(document);
    var isRtl = $('html').attr('dir') === 'rtl';

    $root.find('.related-projects-slider').each(function () {
      var $slider = $(this);

      if ($slider.hasClass('slick-initialized')) {
        return;
      }

      var desiredSlides = parseInt($slider.data('related-slides'), 10);
      if (!desiredSlides || desiredSlides < 1) {
        desiredSlides = 5;
      }
      desiredSlides = Math.min(desiredSlides, 5);

      $slider.slick({
        rtl: isRtl,
        autoplay: true,
        autoplaySpeed: 2000,
        pauseOnHover: true,
        slidesToShow: desiredSlides,
        slidesToScroll: Math.min(2, desiredSlides),
        dots: false,
        arrows: true,
        prevArrow: '<button aria-label="previous" class="slick-prev"> <i class="' + (isRtl ? 'icon-right-open' : 'icon-left-open') + '"></i></button>',
        nextArrow: '<button aria-label="next" class="slick-next"> <i class="' + (isRtl ? 'icon-left-open' : 'icon-right-open') + '"></i></button>',
        responsive: [
          {
            breakpoint: 1400,
            settings: {
              slidesToShow: Math.min(4, desiredSlides),
              slidesToScroll: Math.min(2, desiredSlides)
            }
          },
          {
            breakpoint: 1024,
            settings: {
              slidesToShow: Math.min(3, desiredSlides),
              slidesToScroll: 1
            }
          },
          {
            breakpoint: 768,
            settings: {
              slidesToShow: 2,
              slidesToScroll: 1
            }
          },
          {
            breakpoint: 520,
            settings: {
              slidesToShow: 1,
              slidesToScroll: 1
            }
          }
        ]
      });
    });
  }

  $(document).ready(function () {
    initRelatedProjectsSliders(document);
  });

  $(document).on('aqarand:related-projects-refresh', function (event, context) {
    initRelatedProjectsSliders(context || document);
  });
})(jQuery);
