/* カスタマイズ用Javascript */

$(function() {
    $('.cs-toggle-icon a').on('click', function() {
        $(this).find('.fa.fa-lg').removeClass('fa-angle-up');
        $(this).find('.fa.fa-lg').removeClass('fa-angle-down');

        target = $(this).attr('href');
        $(target).toggle();

        if ($(target).is(':visible')) {
            $(this).find('.fa.fa-lg').addClass('fa-angle-up');
        } else {
            $(this).find('.fa.fa-lg').addClass('fa-angle-down');
        }
    });
});