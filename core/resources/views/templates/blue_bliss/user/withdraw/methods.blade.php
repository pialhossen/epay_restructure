@extends($activeTemplate . 'layouts.master')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <form action="{{ route('user.withdraw.money') }}" method="post" class="withdraw-form disableSubmission">
                @csrf
                <div class="gateway-card">
                    <div class="text-center card-header flex-column py-3">
                        <h5 class="d-block">@lang('Withdraw Balance')</h5>
                        <span>@lang('Your Current Balance Is:') {{ showAmount($user->balance) }}</span>
                    </div>
                    <div class="row justify-content-center gy-sm-4 gy-3">
                        <div class="col-lg-6">
                            <div class="payment-system-list is-scrollable gateway-option-list">
                                @foreach ($currencies as $data)
                                <label for="{{ titleToKey($data->name) }}_{{ $data->cur_sym }}"
                                    class="payment-item @if ($loop->index > 4) hidden-currency @endif gateway-option"
                                    @if ($loop->index > 4) style="display: none" @endif
                                >
                                    <div class="payment-item__info">
                                        <span class="payment-item__check"></span>
                                        <span class="payment-item__name">
                                            {{ __($data->name) }} -
                                            {{ $data->cur_sym }}
                                        </span>
                                    </div>
                                    <div class="payment-item__thumb">
                                        <img class="payment-item__thumb-img"
                                            src="{{ getImage(getFilePath('currency') . '/' . $data->image) }}"
                                            alt="@lang('payment-thumb')">
                                    </div>
                                         <input class="payment-item__radio gateway-input"
                                        id="{{ titleToKey($data->name) }}_{{ $data->cur_sym }}" hidden
                                        data-gateway='@json($data)'
                                        type="radio"
                                        name="currency_id"
                                        value="{{ $data->id }}"
                                        data-min-amount="{{ $data->minimum_limit_for_sell }}"
                                        data-max-amount="{{ $data->maximum_limit_for_sell }}"
                                        data-default-rate="{{ $data->sell_at }}"
                                        data-symbol="{{ $data->cur_sym }}"
                                        data-charge="{{ $data->percent_charge_for_sell}}"
                                        >


                                </label>
                                @endforeach
                                @if ($currencies->count() > 4)
                                <button type="button" class="payment-item__btn more-gateway-option">
                                    <p class="payment-item__btn-text">@lang('Show All Payment Options')</p>
                                    <span class="payment-item__btn__icon"><i
                                            class="fas fa-chevron-down"></i></i></span>
                                </button>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="payment-system-list">
                                <div class="deposit-info">
                                    <div class="deposit-info__title">
                                        <p class="text mb-0">@lang('Send Amount')</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <div class="deposit-info__input-group input-group">
                                            <span class="deposit-info__input-group-text">{{ $account_currency->cur_sym }}</span>
                                            <input type="text" class="form-control form--control amount"
                                                name="amount" placeholder="@lang('00.00')"
                                                value="{{ old('amount') }}" autocomplete="off" step="any" disabled>
                                        </div>
                                    </div>
                                </div>

                                @if(auth()->user()->is_exchange_rate_permission == 1)
                                <div class="deposit-info mt-3">
                                    <div class="deposit-info__title">
                                        <p class="text mb-0">@lang('Withdraw Rate')</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <div class="deposit-info__input-group input-group">
                                            <span class="deposit-info__input-group-text"></span>
                                            <input type="text" class="form-control form--control custom_rate"
                                                name="custom_rate" value="{{ old('custom_rate') }}"
                                                placeholder="@lang('00.00')" autocomplete="off" step="any" disabled>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="deposit-info mt-3">
                                    <div class="deposit-info__title">
                                        <p class="text mb-0">@lang('Get Amount')</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <div class="deposit-info__input-group input-group">
                                            <span class="deposit-info__input-group-text getAmountCurrency"></span>
                                            <input type="text" class="form-control form--control getAmount"
                                                name="get_amount" value="{{ old('get_amount') }}"
                                                placeholder="@lang('00.00')" autocomplete="off" step="any" disabled>
                                        </div>
                                        <div class="text-success show-rate" style="display: none;">Rate: 1 A/C Balance = <span></span></div>
                                    </div>
                                </div>
                                <hr>
                                <div class="deposit-info">
                                    <div class="deposit-info__title">
                                        <p class="text has-icon"> @lang('Limit')</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text"><span class="gateway-limit">@lang('0.00')</span> </p>
                                    </div>
                                </div>
                                @foreach ($charges as $charge)
                                <div class="deposit-info currency_charge currency_charge_{{ $charge->currency_id }} charge_{{ $charge->id }}" style="display: none;">
                                    <div class="deposit-info__title">
                                        <p class="text has-icon">@lang($charge->title)</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text">
                                            <span class="processing-fee">@lang('0.00')</span>
                                        </p>
                                    </div>
                                </div>
                                @endforeach


                                @if(count($ac_charges) > 0)
                                <div class="deposit-info ac-balance-container pt-3">
                                    <div class="deposit-info__title">
                                        <p class="text">@lang('Total A/C Balance charge')</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text">
                                            <span class="ac-balance-charge">@lang('0.00')</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="deposit-info ac-balance-container pt-3">
                                    <div class="deposit-info__title">
                                        <p class="text">@lang('Total A/C Balance')</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text">
                                            <span class="ac-balance">@lang('0.00')</span>
                                        </p>
                                    </div>
                                </div>
                                @endif



                                <div class="deposit-info total-amount pt-3">
                                    <div class="deposit-info__title">
                                        <p class="text">@lang('Receivable')</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text">
                                            <span class="final-amount">@lang('0.00')</span>
                                        </p>
                                    </div>
                                </div>
                                @foreach ($currencies as $currency)
                                    @if(isset($currency->form_fields['form_data']))
                                        @foreach ($currency->form_fields['form_data'] as $form_field)
                                            <div style="display: none;" class="mb-2 field field_{{ $currency->id }}">
                                                <div>
                                                    <label for="test-id" style="font-size: 16px;">{{ $form_field['name'] }}</label>
                                                    <span data-bs-toggle="tooltip" title="{{ $form_field['instruction'] }}"
                                                        class="processing-fee-info">
                                                        <i class="las la-info-circle"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <input id="test-id" name="{{ $form_field['label'] }}" type="{{ $form_field['type'] }}" class="form-control form--control" {{ $form_field['is_required'] }} disabled>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                @endforeach
                                <div class="sending_info d-none">
                                    <div class="deposit-info total-amount pt-3 mb-3">
                                        <div class="deposit-info__title">
                                            <h6 class="text">@lang('Sending Information')</h6>
                                        </div>
                                    </div>
                                    <div class="user_input form-group"> </div>
                                </div>
                                <button type="submit" class="btn btn--base w-100" >
                                    @lang('Confirm Withdraw')
                                </button>
                                <div class="info-text pt-3">
                                    <p class="text m-0">
                                        @lang('Safely withdraw your funds using our highly secure process and various withdrawal method')
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')


<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".floating_number").forEach(input => {
        input.addEventListener("input", function() {
            // Remove everything except digits and "."
            this.value = this.value.replace(/[^0-9.]/g, '');

            // Prevent more than one "."
            if ((this.value.match(/\./g) || []).length > 1) {
            this.value = this.value.slice(0, -1);
            }

            // Auto-fix ".5" → "0.5"
            if (this.value.startsWith(".")) {
            this.value = "0" + this.value;
            }
        });
    });


    const radios = document.querySelectorAll(".gateway-input");
    const getAmountCurrency = document.querySelector(".getAmountCurrency");
    const defaultRate = document.querySelector("#default_rate");
    const gatewayLimit = document.querySelector(".gateway-limit");
    const processingFee = document.querySelector(".processing-fee");
    const finalAmount = document.querySelector(".final-amount");
    const sendAmountInput = document.querySelector(".amount");
    const getAmountInput = document.querySelector(".getAmount");
    const customRate = document.querySelector(".custom_rate");
    const showRate = document.querySelector('.show-rate');
    const moreGatewayOption = document.querySelector('.more-gateway-option');
    const hidden_currency = document.querySelectorAll('.hidden-currency')

    const ac_charges = @json($ac_charges)

    const allChargeDiscount = @json($charges);
    const currencies = @json($currencies);
    let current_currency = {};
    let currentCharges = [];


    moreGatewayOption.addEventListener('click', (e) => {
        hidden_currency.forEach(currency => {
            currency.style.display = 'flex'
        })
        moreGatewayOption.style.display = 'none'
    })
   

    function updateUI(selected) {
        showRate.style.display = 'block';

        let defaultRate = parseFloat(selected.getAttribute("data-default-rate")) || 0;
        if(customRate){
            customRate.value = defaultRate
        }
        const id = selected.value;
        currentCharges = [];
        currencies.forEach(currency => {
            if(Number(currency.id) === Number(id)){
                current_currency = currency
            }
        })
        showRate.querySelector('span').innerHTML = parseFloat(current_currency.sell_at).toFixed(current_currency.show_number_after_decimal) + ' ' + current_currency.cur_sym
        allChargeDiscount.forEach(charge => {
            if(Number(charge.currency_id) === Number(id)){
                currentCharges.push(charge)
            }
        })
        const charges = document.querySelectorAll(`.currency_charge`)
        const current_charges = document.querySelectorAll(`.currency_charge_${id}`)
        const form_fields = document.querySelectorAll(`.field`);
        const current_currency_form_fields = document.querySelectorAll(`.field_${id}`);

        charges.forEach(charge => {
            charge.style.display = 'none'
        })

        form_fields.forEach(field => {
            field.style.display = 'none';
            field.querySelector('input').setAttribute("disabled","true");
        })
        current_currency_form_fields.forEach(field => {
            field.style.display = 'block';
            field.querySelector('input').removeAttribute("disabled");
        })

        let minAmount = selected.getAttribute("data-min-amount");
        let maxAmount = selected.getAttribute("data-max-amount");
        let rate = selected.getAttribute("data-default-rate");
        let symbol = selected.getAttribute("data-symbol");
        let charge = selected.getAttribute("data-charge");
        const current_currency_symbol = current_currency.cur_sym;

        // ডান পাশের UI আপডেট হবে
        getAmountCurrency.textContent = symbol;
        defaultRate.textContent = rate;
        gatewayLimit.textContent = parseFloat(minAmount).toFixed(current_currency.show_number_after_decimal) + ` ${current_currency_symbol}` + " - " + parseFloat(maxAmount).toFixed(current_currency.show_number_after_decimal) + ` ${current_currency_symbol}`;
        processingFee.textContent = parseFloat(charge).toFixed(2);
        finalAmount.textContent = "0.00";
        getAmountInput.value = "";
        sendAmountInput.value = "";
    }

    // যখন currency select হবে
    radios.forEach(radio => {
        radio.addEventListener("change", function () {
            sendAmountInput.removeAttribute('disabled');
            getAmountInput.removeAttribute('disabled');
            if(customRate){
                customRate.removeAttribute('disabled');
            }
            updateUI(this);
        });
    });

    function calculate_ac_charges(){
        if(ac_charges.length <= 0) return
        const ac_balance_charge = document.querySelector('.ac-balance-charge') 
        const ac_balance = document.querySelector('.ac-balance') 

        const send_amount = sendAmountInput.value
        let total_ac_charge = 0
        ac_charges.forEach(charge => {
            if(charge.from > send_amount && charge.to < send_amount) return
            const fixed_amount = charge.charge_fixed
            const percent_amount = (charge.charge_percent / 100) * send_amount
            total_ac_charge = fixed_amount + percent_amount
        })
        ac_balance_charge.innerHTML = total_ac_charge.toFixed(4)
        ac_balance.innerHTML = (parseFloat(send_amount) + total_ac_charge).toFixed(4)
    }

    function calculate_charges(amount){
        

        amount = amount? amount: 0
        let total_charges = 0
        const current_currency_symbol = current_currency.cur_sym
        currentCharges.forEach(charge => {
            if(Number(amount) >= Number(charge.from) && Number(amount) <= Number(charge.to)){
                const charge_element_container = document.querySelector(`.charge_${charge.id }`)
                charge_element_container.style.display = 'flex'
                const charge_fixed = charge.charge_fixed? charge.charge_fixed: 0
                const charge_percent = charge.charge_percent? charge.charge_percent: 0
                const charge_percent_amount = (charge_percent / 100) * amount
                const total_charge = charge_fixed + charge_percent_amount 

                total_charges += total_charge


                const charge_element = document.querySelector(`.charge_${charge.id }`).querySelector('.processing-fee')
                charge_element.innerHTML = total_charge.toFixed(current_currency.show_number_after_decimal) + ` ${current_currency_symbol}`;

            } else {
                const charge_element_container = document.querySelector(`.charge_${charge.id }`)
                charge_element_container.style.display = 'none'
            }
        })
        const finalCalculatedAmount = amount - total_charges
        finalAmount.textContent = finalCalculatedAmount.toFixed(2)+` ${current_currency_symbol}`;

        calculate_ac_charges()
    }


    sendAmountInput.addEventListener("input", function () {

        let selected = document.querySelector(".gateway-input:checked");
        if (!selected) return;

        let rate = parseFloat(selected.getAttribute("data-default-rate"));
        const custom_rate =  parseFloat(customRate?.value)? parseFloat(customRate.value): 0;
        let amount = parseFloat(this.value) || 0;
        let chargePercent = parseFloat(selected.getAttribute("data-charge")) || 0;

        let converted = amount / (custom_rate?custom_rate:rate);
        let fee = (converted * chargePercent) / 100;
        let receivable = converted - fee;

        getAmountInput.value = converted.toFixed(2);
        processingFee.textContent = fee.toFixed(2);
        finalAmount.textContent = receivable.toFixed(2);
        calculate_charges(converted)
    });

    getAmountInput.addEventListener("input", function (e) {

        let selected = document.querySelector(".gateway-input:checked");
        if (!selected) return;

        let rate = parseFloat(selected.getAttribute("data-default-rate"));
        const custom_rate =  parseFloat(customRate?.value)? parseFloat(customRate.value): 0;
        let getAmount = parseFloat(this.value) || 0;
        let chargePercent = parseFloat(selected.getAttribute("data-charge")) || 0;

        let requiredSend = getAmount * (custom_rate?custom_rate:rate);

        sendAmountInput.value = requiredSend.toFixed(2);
        finalAmount.textContent = getAmount.toFixed(2);
        calculate_charges(getAmount)
    });
    if(customRate){
        customRate.addEventListener("input", function(e){
            
            let selected = document.querySelector(".gateway-input:checked");
            if (!selected) return;
            
            let rate = parseFloat(selected.getAttribute("data-default-rate"));
            const custom_rate =  parseFloat(e.target.value);
            let amount = parseFloat(sendAmountInput.value) || 0;
            let chargePercent = parseFloat(selected.getAttribute("data-charge")) || 0;
    
            let converted = amount / (custom_rate?custom_rate:rate);
            let fee = (converted * chargePercent) / 100;
            let receivable = converted - fee;
    
            getAmountInput.value = converted.toFixed(2);
            processingFee.textContent = fee.toFixed(2);
            finalAmount.textContent = receivable.toFixed(2);
            calculate_charges(converted)
        })
    }
    

    let defaultSelected = document.querySelector(".gateway-input:checked");
    if (defaultSelected) {
        updateUI(defaultSelected);
    }
});
</script>


@endpush

@push('style')
<style>
    .form--control {
        height: 44px;
    }
</style>
@endpush