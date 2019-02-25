(function ($) {

  $(document).ready(function () {
    var jqSelects = $("select.form-control");
    $.each(jqSelects, (index, element) => {
      let selectElement = $(element);
      let placeholder = (selectElement.attr('title') === undefined) ? 'Please select' : selectElement.attr('title');
      $(element).select2({
        placeholder: placeholder,
        allowClear: true,
        language: "fr",
        width: '100%'
      })
    });

    // S'inscrire pour une demande de formation
    $('span.request-formation').on('click', (ev) => {
      ev.preventDefault();
      let id = $(ev.currentTarget).data('id');
      let subject = $(ev.currentTarget).data('subject');
      swal({
          title: "Confirmation",
          text: `Voulez-vous s'inscrire à cette demande de formation: ${subject}?`,
          type: "info",
          showCancelButton: true,
          confirmButtonClass: "btn-blue ml-2",
          confirmButtonText: "S'inscrire",
          cancelButtonText: "Annuler",
          closeOnConfirm: false,
          showLoaderOnConfirm: true
        },
        function () {
          $.ajax({
            url: `${itOptions.ajax}`,
            type: "POST",
            dataType: 'json',
            data: {
              action: "request_formation_concerned",
              request_formation_id: parseInt(id)
            },
            beforeSend: function (xhr) {
            },
            success: function (response) {
              let title = response.success ? "Succès" : "Erreur";
              let icon = response.success ? 'success' : 'error';
              swal(title, response.data, icon);
            },
            error: function (error) {
              swal('Désolé', "Une erreur s'est produite", 'error');
            }
          });
        });
    })
  });


})(jQuery);