$('.aside-nav').on('show.bs.dropdown', function () {
    $('.aside-nav').removeClass("ps").addClass('overflow-custom-fixer');
});

$('.aside-nav').on('hide.bs.dropdown', function () {
    $('.aside-nav').removeClass("overflow-custom-fixer").addClass("ps");
})
