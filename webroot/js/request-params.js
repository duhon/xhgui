// Utilitarian DOM behavior.
$(document).ready(function () {
    $('#view-request-params').click(function () {
        $('#request-params-details').addClass('active');
        $('#params-json-viwer').jsonViewer(JSON.parse($('.request-params-details-content').attr('data-params')));
    });

    $('#request-params-details').find('.request-params-button-close').on('click', function() {
        $('#request-params-details').removeClass('active');
        return false;
    });
});
