(function () {
    'use strict';
    jQuery(document.body).on('init_add_payment_method payment_method_selected', function () {
        setTimeout(function () {
            angelleyeOrder.CCAddPaymentMethod();
        }, 1000);
    });
})(jQuery);