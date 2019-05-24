// Utilitarian DOM behavior.
$(document).ready(function () {
    $(document).on("click", ".view-request-params", function () {
        $('#request-params-details').addClass('active');
        $('#request-params-details').find('#query-details').find("pre").text($(this).attr('data-query'));

        var p = JSON.parse(unescape($(this).attr('data-params')));
        var pc = '';
        
        p.forEach(function (e, i) {
            pc += '<pre>';
            pc += '<h4>Run #' + (i+1) + '</h4>';

            e.forEach(function (e2, i2) {
                pc += '#' + (i2+1) + ': ' + e2 + '<br>';
            });

            pc += '</pre>';
        });

        $('#request-params-details').find('#params-details').find(".content").html(pc);
    });

    $('#request-params-details').find('.request-params-button-close').on('click', function() {
        $('#request-params-details').removeClass('active');
        $('#request-params-details').find('#query-details').find("pre").text("");
        $('#request-params-details').find('#params-details').find(".content").html("");
        return false;
    });
});
