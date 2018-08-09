/*
 *   Copyright (c) 2018, Falicrea
 *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy
 *   of this software and associated documentation files, to deal
 *   in the Software without restriction, including without limitation the rights
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *   copies of the Software, and to permit persons to whom the Software is
 *   furnished to do so, subject to the following conditions:
 *
 *   The above copyright notice and this permission notice shall be included in all
 *   copies or substantial portions of the Software.
 */

(function ($) {
  $(document).ready(function () {
    // load a locale
    numeral.register('locale', 'mg', {
      delimiters: {
        thousands: ' ',
        decimal: ','
      },
      abbreviations: {
        thousand: 'k',
        million: 'm',
        billion: 'b',
        trillion: 't'
      },
      currency: {
        symbol: 'MGA'
      }
    });
    numeral.locale('mg');

    $.each($('.price'), function (key, element) {
      var number = null;
      $(element).text(function () {
        number = parseFloat($(element).text());
        if (isNaN(number)) return $(element).text();
        return numeral(number).format('0,0 $');
      });
    });

    var product_list = $('.card.product-list');
    $.each(product_list, function (key, element) {
      $(element)
        .click(function () {
          window.location.href = $(this).data('url');
        });
    })
  });
})(jQuery);
