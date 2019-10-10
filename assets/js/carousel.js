(function ($) {
    $(document).ready(function () {
        $('.owl-carousel').owlCarousel({
            items: 1,
            animateOut: 'fadeOut',
            loop: true,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: false,
            pagination: false,
            dots: true,
            margin: 10,
            autoHeight: false,
        });
    });
})(jQuery);