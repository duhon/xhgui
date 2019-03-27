// Utilitarian DOM behavior.
$(document).ready(function () {
    $('.view-request-params').click(function () {
        $('#request-params-details').addClass('active');
        $('#params-json-viewer').jsonViewer(JSON.parse($(this).attr('data-params')));
    });

    $('#request-params-details').find('.request-params-button-close').on('click', function() {
        $('#request-params-details').removeClass('active');
        $('#params-json-viewer').html("");
        return false;
    });
});
