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
  var priceElement = $('.price');
  $.each(priceElement, function (key, element) {
    if ($(element).isClass('numeral')) return;
    var number = null;
    $(element).text(function () {
      number = parseFloat($(element).text());
      if (isNaN(number)) return $(element).text();
      $(element).addClass('numeral');
      return numeral(number).format('0,0 $');
    });
  });
})(jQuery);