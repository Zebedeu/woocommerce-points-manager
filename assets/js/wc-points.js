(function ($) {
    function formatMoney(n, c, d, t) {
        var c = isNaN(c = Math.abs(c)) ? 2 : c,
            d = d == undefined ? "." : d,
            t = t == undefined ? "," : t,
            s = n < 0 ? "-" : "",
            i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
            j = (j = i.length) > 3 ? j % 3 : 0;

        return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
    };
    $(document).ready(function () {
        updated_cart();
        $( document ).ajaxComplete(function(event, xhr, settings) {
            console.log(event, xhr, settings);
            if ($('.woocommerce-cart').length) {
                updated_cart();
            }
        });
    });
    function updated_cart() {
        if ($('.woocommerce-cart').length) {
            var minPointsToUse, maxPointsToUse;
            if (wc_points.minPointsToUseIsPercent == 1) {
                minPointsToUse = wc_points.cartSubtotalWithShipping * (wc_points.minPointsToUse / 100);
            } else {
                minPointsToUse = parseFloat(wc_points.minPointsToUse);
            }
            if (wc_points.maxPointsToUseIsPercent == 1) {
                maxPointsToUse = wc_points.cartSubtotalWithShipping * (wc_points.maxPointsToUse / 100);
            } else {
                maxPointsToUse = wc_points.maxPointsToUse > 0 ? parseFloat(wc_points.maxPointsToUse) : wc_points.cartSubtotalWithShipping;
            }
            minPointsToUse = minPointsToUse.toFixed(2);
            console.log(minPointsToUse);
            $('#wc-points-cash').attr({
                min: minPointsToUse,
                max: maxPointsToUse
            }).val(wc_points.cartDiscount).change(function () {
                var cash_discount = parseFloat($(this).val());
                cash_discount = cash_discount.toFixed(2);
                var cash = wc_points.cartSubtotalWithShipping - cash_discount;
                var points_to_redeem = cash_discount * wc_points.userFactor;
                $('#wc-points-to-redeem').html(formatMoney(points_to_redeem, 2, ',', '.'));
                $('#wc-points-to-cash').html(formatMoney(cash, 2, ',', '.'));
            }).change();
        }
    }
})(jQuery);