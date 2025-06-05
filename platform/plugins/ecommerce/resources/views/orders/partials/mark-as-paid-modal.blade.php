<x-core::modal.action
    id="confirm-payment-modal"
    type="info"
    :title="trans('plugins/ecommerce::order.confirm_payment')"
    :description="trans('plugins/ecommerce::order.confirm_payment_confirmation_description', [
        'method' => 'manual',
    ])"
    :submit-button-attrs="['id' => 'confirm-payment-order-button']"
    :submit-button-label="trans('plugins/ecommerce::order.confirm_payment')"
/>

<script>
    'use strict';
    
    $(document).ready(function () {
        $(document).on('click', '.btn-trigger-confirm-payment', function (event) {
            event.preventDefault();
            $('#confirm-payment-order-button').data('target', $(event.currentTarget).data('target'));
            $('#confirm-payment-modal').modal('show');
        });

        $(document).on('click', '#confirm-payment-order-button', function (event) {
            event.preventDefault();
            const _self = $(event.currentTarget);

            $httpClient
                .make()
                .withButtonLoading(_self)
                .post(_self.data('target'))
                .then(({ data }) => {
                    if (!data.error) {
                        Botble.showSuccess(data.message);
                        window.location.reload();
                    } else {
                        Botble.showError(data.message);
                    }
                    $('#confirm-payment-modal').modal('hide');
                });
        });
    });
</script>
