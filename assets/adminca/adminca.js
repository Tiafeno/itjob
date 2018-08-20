/* ------------------------------------------------------------------------------
*  # Adminca Template JS file
* ---------------------------------------------------------------------------- */

(function ($) {
  $(document).ready(function () {
    $('.ibox-collapse').click(function () {
      var ibox = $(this).closest('div.ibox');
      ibox.toggleClass('collapsed-mode').children('.ibox-body').slideToggle(200);
    });
    $('.ibox-remove').click(function () {
      $(this).closest('div.ibox').remove();
    });
    $('.fullscreen-link').click(function () {
      if ($('body').hasClass('fullscreen-mode')) {
        $('body').removeClass('fullscreen-mode');
        $(this).closest('div.ibox').removeClass('ibox-fullscreen');
        $(window).off('keydown', toggleFullscreen);
      } else {
        $('body').addClass('fullscreen-mode');
        $(this).closest('div.ibox').addClass('ibox-fullscreen');
        $(window).on('keydown', toggleFullscreen);
      }
    });

    function toggleFullscreen(e) {
      // pressing the ESC key - KEY_ESC = 27
      if (e.which == 27) {
        $('body').removeClass('fullscreen-mode');
        $('.ibox-fullscreen').removeClass('ibox-fullscreen');
        $(window).off('keydown', toggleFullscreen);
      }
    }
  });

})(jQuery);





