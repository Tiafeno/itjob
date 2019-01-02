(function ($) {

  $(document).ready(function () {
    var jqSelects = $("select.form-control");
    $.each(jqSelects, function (index, element) {
      var selectElement = $(element);
      var placeholder = (selectElement.attr('title') === undefined) ? 'Please select' : selectElement.attr('title');
      $(element).select2({
        placeholder: placeholder,
        allowClear: true,
        language: "fr",
        width: '100%'
      })
    });
  });

})(jQuery);