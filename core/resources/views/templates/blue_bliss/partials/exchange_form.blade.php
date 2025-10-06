@php
    $site_path = '/'.APP_PUBLIC_FOLDER;
@endphp
<form class="exchange-form disableSubmission" method="POST" action="{{ route('exchange.start') }}" id="exchange-form">
    @csrf
    <div class="form-group sendData">
        <div class="input-wrapper">
            <input type="number" step="any" name="sending_amount" id="sending_amount" class="form--control"
                placeholder="@lang('You Send')">
            <input type="hidden" id="sending_amount_hidden">
            <select required class="select2 form-control form--control sending_currency" data-type="select" name="sending_currency"
                id="send">
                <option value="" selected disabled>@lang('Select One')</option>
                @foreach ($sellCurrencies as $sellCurrency)
                    <option
                        data-image="{{ getImage(getFilePath('currency') . '/' . @$sellCurrency->image, getFileSize('currency')) }}"
                        data-min="{{ $sellCurrency->minimum_limit_for_buy }}"
                        data-max="{{ $sellCurrency->maximum_limit_for_buy }}" data-buy="{{ $sellCurrency->buy_at }}"
                        data-currency="{{ @$sellCurrency->cur_sym }}"
                        data-show_number="{{ @$sellCurrency->show_number_after_decimal }}" value="{{ $sellCurrency->id }}"
                        class="send" data-select-for="send" @selected(old('sending_currency') == $sellCurrency->id)>
                        {{ __($sellCurrency->name) }} - {{ __($sellCurrency->cur_sym) }}
                    </option>
                @endforeach
            </select>
        </div>

        <span class="d-none" id="currency-limit"></span>
    </div>

    <span class="exchange-form__icon">
        <i class="las la-exchange-alt"></i>
    </span>
    <div class="form-group receiveData ">
        <div class="input-wrapper">
            <input type="number" step="any" name="receiving_amount" class="form--control" id="receiving_amount"
             placeholder="@lang('You Get')" required>
            <input type="hidden" id="receiving_amount_hidden">
            <select class="select2 form-control form--control receiving_currency" name="receiving_currency" id="receive" required
                value.bind="selectedThing2">


                <option value="" selected disabled>@lang('Select One')</option>
                @foreach ($buyCurrencies as $buyCurrency)
                    <option
                        data-image="{{ getImage(getFilePath('currency') . '/' . @$buyCurrency->image, getFileSize('currency')) }}"
                        data-sell="{{ $buyCurrency->sell_at }}" data-currency="{{ @$buyCurrency->cur_sym }}"
                        data-min="{{ $buyCurrency->minimum_limit_for_sell }}"
                        data-max="{{ $buyCurrency->maximum_limit_for_sell }}" data-reserve="{{ $buyCurrency->reserve }}"
                        value="{{ $buyCurrency->id }}" 
                        data-show_number="{{ @$buyCurrency->show_number_after_decimal }}"
                        data-select-for="received" @selected(old('receiving_currency') == $buyCurrency->id)>
                        {{ __($buyCurrency->name) }} - {{ __($buyCurrency->cur_sym) }}
                    </option>
                @endforeach
            </select>
        </div>
        <span class="d-none" id="currency-limit-received"></span>
    </div>

    <input type="hidden" name="selling_rate" id="selling_rate" value="">
    <input type="hidden" name="buying_rate" id="buying_rate" value="">

    <div class="exchange-btn">
        <button type="submit" class="btn--base btn">@lang('Exchange')</button>
    </div>
</form>



@push('style-lib')
    <link href="{{ asset('assets/global/css/select2.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush

@push('script')
    <script>
        "use strict";

        let userHasExchangePermission = false;

        @auth
            userHasExchangePermission = {!! json_encode(auth()->user()->is_exchange_rate_permission) !!};
        @endauth

        (function ($) {
            let sendId,
                sendMinAmount,
                sendMaxAmount,
                sendAmount,
                sendCurrency,
                sendCurrencyBuyRate;
            let receivedId,
                receivedAmount,
                receivedCurrency,
                receiveCurrencySellRate,
                sendShowNumber,
                receivingShowNumber,
                minAmount,
                maxAmount,
                reserveAmount;


            //=============change select2 structure
            $('.select2').select2({
                templateResult: formatState
            });

            function formatState(state) {
                if (!state.id) return state.text;
                let selectType = $(state.element).data('select-for').toUpperCase();
                if (sendId && selectType == 'RECEIVED' && sendId == state.element.value) {
                    return false;
                }
                if (receivedId && selectType == 'SEND' && receivedId == state.element.value) {
                    return false;
                }
                return $('<img class="ms-1"   src="' + $(state.element).data('image') + '"/> <span class="ms-3">' +
                    state.text + '</span>');
            }
            function toFixedTruncate(value, decimals) {
                const factor = Math.pow(10, decimals);
                return (Math.trunc(value * factor) / factor).toFixed(decimals);
            }

            $(document).ready(function () {
                let selectedSendId = null;
                let selectedReceiveId = null;

                $('#sending_amount').off('input').on('input', function (e) {
                    $('#sending_amount_hidden').val(e.target.value)
                })
                $('#receiving_amount').off('input').on('input', function (e) {
                    $('#receiving_amount_hidden').val(e.target.value)
                })

                $('[name=sending_currency]').on('change', function () {
                    selectedSendId = $(this).val();
                    selectedReceiveId = $('[name=receiving_currency]').val();

                    if (selectedSendId && selectedReceiveId) {
                        fetchBestRates(selectedSendId, selectedReceiveId);
                    } else {
                        $(".best-rate-slide").addClass("d-none").removeClass("show");
                    }

                    fetchReceivingCurrencies(selectedSendId);
                });

                $('[name=receiving_currency]').on('change', function () {
                    selectedReceiveId = $(this).val();
                    selectedSendId = $('[name=sending_currency]').val();

                    if (selectedSendId && selectedReceiveId) {
                        fetchBestRates(selectedSendId, selectedReceiveId);
                    } else {
                        $(".best-rate-slide").addClass("d-none").removeClass("show");
                    }
                });

                function fetchReceivingCurrencies(sendId) {
                    if (!sendId) return;

                    $.ajax({
                        url: `{{ $site_path }}/user/receiving-currencies?sending_currency=${sendId}`,
                        type: "GET",
                        success: function (response) {
                            const $receiveSelect = $('[name="receiving_currency"]');
                            $receiveSelect.empty(); // clear existing options

                            if (response.length > 0) {
                                $receiveSelect.append(
                                    `<option value="" disabled selected>Select One</option>`);

                                response.forEach(currency => {
                                    $receiveSelect.append(
                                        `<option
                                                data-image="${currency.image_url}"
                                                data-sell="${currency.sell_at}"
                                                data-currency="${currency.cur_sym}"
                                                data-min="${currency.minimum_limit_for_sell}"
                                                data-max="${currency.maximum_limit_for_sell}"
                                                data-reserve="${currency.reserve}"
                                                data-show_number="${currency.show_number_after_decimal}"
                                                data-select-for="received"
                                                value="${currency.id}">
                                                ${currency.name} - ${currency.cur_sym}
                                            </option>`
                                    );
                                });

                                // Refresh Select2 to re-render options with images
                                $receiveSelect.trigger('change.select2');
                            } else {
                                $receiveSelect.append(
                                    `<option disabled>No receiving currencies available</option>`
                                );
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error("Error fetching receiving currencies:", error);
                            notify('error', 'Failed to fetch receiving currencies');
                        }
                    });
                }


                function fetchBestRates(sendId, receiveId) {
                    $.ajax({
                        url: `{{ route('exchange.best.rates') }}`,
                        type: "GET",
                        data: {
                            sending_currency: sendId,
                            receiving_currency: receiveId
                        },
                        beforeSend: function () {
                            $(".best-rate-list").html(
                                '<li class="list-group-item text-center">Loading...</li>');
                            $(".best-rate-slide").removeClass("d-none").addClass("show");
                            $('.sending_currency').attr('disabled','true')
                            $('.receiving_currency').attr('disabled','true')
                            
                        },
                        success: function (response) {
                            if (response.rates && response.rates.length > 0) {
                                updateBestRatesUI(response.rates);
                            } else {
                                $(".best-rate-list").html(
                                    '<li class="list-group-item text-warning text-center">No rates available</li>'
                                );
                                $(".best-rate-slide").removeClass("show").addClass("d-none");
                            }
                            $('.sending_currency').removeAttr('disabled')
                            $('.receiving_currency').removeAttr('disabled')
                        },
                        error: function (xhr, status, error) {
                            console.error("Error fetching best rates:", error);
                            $(".best-rate-list").html(
                                '<li class="list-group-item text-danger text-center">Failed to load rates</li>'
                            );
                            $(".best-rate-slide").removeClass("show").addClass("d-none");
                            $('.sending_currency').removeAttr('disabled')
                            $('.receiving_currency').removeAttr('disabled')
                        },
                    });
                }

                function updateBestRatesUI(rates) {
                    let rateList = $(".best-rate-list");
                    rateList.empty();

                    if (rates.length === 0) {
                        rateList.html(
                            '<li class="list-group-item text-warning text-center">No rates available</li>'
                        );
                        return;
                    }

                    // Deduplicate by receiving_currency_id (adjust key if different)
                    rates = uniqueBy(rates, rate => rate.receiving_currency_id);


                    let selectedSendOption = $('[name="sending_currency"] option:selected');
                    let selectedReceiveOption = $('[name="receiving_currency"] option:selected');

                    let sendNameAndSymbol = selectedSendOption.text() || '';
                    let receiveNameAndSymbol = selectedReceiveOption.text() || '';

                    let html = '';


                    rates.forEach(rate => {
                        let rateValue = parseFloat(rate.rate);
                        let formattedRateValue = 
                            !isNaN(rateValue) && rateValue > 0 ? 
                            rateValue
                            : 'N/A';
                        console.log(formattedRateValue)
                        $('#selling_rate').val(formattedRateValue)
                        let rateRightValue = 
                            !isNaN(rateValue) && rateValue > 0 ? 
                            (1 / rateValue)
                            : '';
                        $('#buying_rate').val(rateRightValue)
                        

                        html += `
                                        <div class="rate-flex-wrapper w-100">
                                            <li class="list-group-item rate-left">
                                                <span class="fw-600 d-flex flex-wrap align-items-center gap-2">
                                                    <input type="number" class="form-control" value="1" readonly />
                                                    ${sendNameAndSymbol} =
                                                    <input 
                                                        type="text" 
                                                        name="received_rate" 
                                                        class="form-control w-auto receive-rate-input" 
                                                        value="${formattedRateValue}" 
                                                        inputmode="decimal"
                                                        oninput="this.value = this.value.replace(/[^0-9.,]/g, '')"  
                                                        ${userHasExchangePermission ? '' : 'readonly'}
                                                    />
                                                    ${receiveNameAndSymbol}
                                                </span>
                                            </li>
                                            <li class="list-group-item rate-right">
                                                <span class="fw-600 d-flex flex-wrap align-items-center gap-2">
                                                    <input 
                                                        type="text" 
                                                        name="received_rate_right" 
                                                        id="received_rate_right"
                                                        class="form-control w-auto receive-rate-right-input" 
                                                        value="${rateRightValue}" 
                                                        inputmode="decimal"
                                                        oninput="this.value = this.value.replace(/[^0-9.,]/g, '')"  
                                                        ${userHasExchangePermission ? '' : 'readonly'}
                                                    />
                                                    ${sendNameAndSymbol} =
                                                    <input type="number" class="form-control" value="1" readonly />
                                                    ${receiveNameAndSymbol}
                                                </span>
                                            </li>
                                        </div>
                                    `;
                    });

                    rateList.html(html);
                    bindRateInputHandlers();

                    // Utility function to dedupe
                    function uniqueBy(arr, keyFn) {
                        const seen = new Set();
                        return arr.filter(item => {
                            const key = keyFn(item);
                            if (seen.has(key)) {
                                return false;
                            } else {
                                seen.add(key);
                                return true;
                            }
                        });
                    }
                }



                function bindRateInputHandlers() {

                    let lastRateType = ''; // Track last edited field
                    let isUpdatingRateInputs = false;

                    // --- A → B input
                    $('.receive-rate-input').off('input blur').on('input', function (e) {
                        $('#selling_rate').val(e.target.value)
                        if (!userHasExchangePermission || isUpdatingRateInputs) return;

                        lastRateType = 'rate';
                        const rateVal = parseFloat($(this).val());
                        const sendAmt = parseFloat($('#sending_amount').val());

                        if (!isNaN(rateVal) && rateVal > 0) {
                            // Calculate the reciprocal for B → A
                            const reciprocal = 1 / rateVal;

                            isUpdatingRateInputs = true;

                            $('.receive-rate-right-input').val(reciprocal); // internal full precision
                            $('#buying_rate').val(rateVal); // store exact rate

                            if (!isNaN(sendAmt)) {
                                const receiveAmt = sendAmt * rateVal;
                                $('#receiving_amount').val(receiveAmt.toFixed(receivingShowNumber));
                            }

                            isUpdatingRateInputs = false;
                        } else {
                            clearRatesAndAmounts();
                        }
                    }).on('blur', function () {
                        const val = parseFloat($(this).val());
                        if (!isNaN(val) && val > 0) $(this).val(val);
                    });

                    // --- B → A input
                    $('.receive-rate-right-input').off('input blur').on('input', function (e) {
                         $('#buying_rate').val(e.target.value)
                        if (!userHasExchangePermission || isUpdatingRateInputs) return;

                        lastRateType = 'rate_right';
                        const rateRightVal = parseFloat($(this).val());
                        const sendAmt = parseFloat($('#sending_amount').val());

                        if (!isNaN(rateRightVal) && rateRightVal > 0) {
                            const reciprocal = 1 / rateRightVal; // A → B rate

                            isUpdatingRateInputs = true;

                            $('.receive-rate-input').val(reciprocal); // internal full precision
                            $('#selling_rate').val(reciprocal);

                            if (!isNaN(sendAmt)) {
                                const receiveAmt = sendAmt * reciprocal;
                                $('#receiving_amount').val(receiveAmt.toFixed(receivingShowNumber));
                            }

                            isUpdatingRateInputs = false;
                        } else {
                            clearRatesAndAmounts();
                        }
                    }).on('blur', function () {
                        const val = parseFloat($(this).val());
                        if (!isNaN(val) && val > 0) $(this).val(val);
                    });

                    // --- Utility to clear inputs safely
                    function clearRatesAndAmounts() {
                        isUpdatingRateInputs = true;
                        $('#received_rate_hidden').val('');
                        $('#receiving_amount').val('');
                        isUpdatingRateInputs = false;
                    }


                    $('#sending_amount').on('input', function (e) {
                        sendAmount = parseFloat($('#sending_amount_hidden').val());
                        if (sendAmount < 0) {
                            sendAmount = 0;
                            notify('error', 'Negative amount is not allowed');
                            $(this).val('');
                            $('input[name="receiving_amount"]').val('');
                        } else {
                            const receive_rate = $('.receive-rate-right-input').val()
                            const reciprocal = 1 / receive_rate
                            const receiving_amount = reciprocal * sendAmount
                            $('#receiving_amount_hidden').val(receiving_amount)
                            $('#receiving_amount').val(receiving_amount.toFixed(receivingShowNumber))
                        }
                    });

                    $('#receiving_amount').on('input', function (e) {
                        receivedAmount = parseFloat($('#receiving_amount_hidden').val());
                        if (receivedAmount < 0) {
                            notify('error', 'Negative amount is not allowed');
                            receivedAmount = 0;
                            $(this).val('');
                            $('input[name="sending_amount"]').val('');
                        } else {
                            const sending_rate = $('.receive-rate-input').val()
                            const reciprocal = 1 / sending_rate
                            const sending_amount = reciprocal * receivedAmount
                            $('#sending_amount_hidden').val(sending_amount);
                            $('#sending_amount').val(sending_amount.toFixed(sendShowNumber));
                        }
                    });

                }
            });

            $('[name=sending_currency]').on('change', function (e) {
                sendId = parseInt($(this).val());
                const selectedOption = $(this).find(':selected');
                sendMinAmount = parseFloat(selectedOption.data('min'));
                sendMaxAmount = parseFloat(selectedOption.data('max'));
                sendCurrency = selectedOption.data('currency');
                sendCurrencyBuyRate = selectedOption.data('buy');
                sendShowNumber = selectedOption.data('show_number');



                // ✅ Currency limit info
                $('#currency-limit').html(
                    `@lang('You Send') <span class="text--base">${sendMinAmount.toFixed(sendShowNumber)}</span> - <span class="text--base">${sendMaxAmount.toFixed(sendShowNumber)}</span> ${sendCurrency}`
                );
                $('#currency-limit').removeClass('d-none').addClass("d-block mt-2");

                $("#sending_amount").siblings('.input-group-text').removeClass('d-none');
                $("#sending_amount").removeClass('rounded');
                $("#sending_amount").siblings('.input-group-text').text(sendCurrency);

                if (sendId) {
                    $(this).closest('.form-group').find('.select2-selection__rendered').html(
                        `<img src="${selectedOption.data('image')}" class="currency-image"/> ${selectedOption.text()}`
                    )
                    calculationReceivedAmount();
                }
            });


            $('[name=receiving_currency]').on('change', function (e) {
                if(!$(this).find(':selected')[0].value){
                    return;
                }
                receivedId = $(this).val() ? parseInt($(this).val()) : null;
                receiveCurrencySellRate = $(this).find(':selected').data('sell');
                receivedCurrency = $(this).find(':selected').data('currency');

                minAmount = parseFloat($(this).find(':selected').data('min'));
                maxAmount = parseFloat($(this).find(':selected').data('max'));
                reserveAmount = parseFloat($(this).find(':selected').data('reserve'));
                receivingShowNumber = $(this).find(':selected').data('show_number');

                $('#currency-limit-received').html(
                    `@lang('Select One')
                                <span class="text--base">${minAmount.toFixed(receivingShowNumber)}</span> - <span class="text--base">${maxAmount.toFixed(receivingShowNumber)}</span>
                                ${receivedCurrency} | Reserve <span class="text--base">${reserveAmount.toFixed(receivingShowNumber)}</span> ${receivedCurrency}`
                );

                $('#currency-limit-received').removeClass('d-none').addClass("d-block mt-2");
                $("#receiving_amount").siblings('.input-group-text').removeClass('d-none');
                $("#receiving_amount").removeClass('rounded');
                $("#receiving_amount").siblings('.input-group-text').text(receivedCurrency);

                if (receivedId) {
                    $(this).closest('.form-group').find('.select2-selection__rendered').html(
                        `<img src="${$(this).find(':selected').data('image')}" class="currency-image"/> ${$(this).find(':selected').text()}`
                    )
                    calculationReceivedAmount();
                }
            });

            $('#sending_amount').on('input', function (e) {
                sendAmount = parseFloat($('#receiving_amount_hidden').val());
                if (sendAmount < 0) {
                    sendAmount = 0;
                    notify('error', 'Negative amount is not allowed');
                    $(this).val('');
                    $('input[name="receiving_amount"]').val('');
                } else {
                    const receive_rate = $('.receive-rate-input').val()
                    const reciprocal = 1 / $receive_rate
                    const receiving_amount = reciprocal * sendAmount
                    $('#receiving_amount_hidden').val(receiving_amount)
                    $('#receiving_amount').val(receiving_amount.toFixed(receivingShowNumber))
                }
            });

            $('#receiving_amount').on('input', function (e) {
                receivedAmount = parseFloat($('#sending_amount_hidden').val());
                if (receivedAmount < 0) {
                    notify('error', 'Negative amount is not allowed');
                    receivedAmount = 0;
                    $(this).val('');
                    $('input[name="sending_amount"]').val('');
                } else {
                    const sending_rate = $('.receive-rate-right-input').val()
                    const reciprocal = 1 / sending_rate
                    const sending_amount = reciprocal * sendAmount
                    $('#sending_amount_hidden').val(sending_amount);
                    $('#sending_amount').val(sending_amount.toFixed(sendShowNumber));
                }
            });

            const waitRateRight = async () => {
                while (true) {
                    await new Promise(resolve => setTimeout(resolve, 100))
                    const sendAmount = parseFloat($('.receive-rate-right-input').val())
                    if (sendAmount) {
                        return sendAmount
                    }
                }
            }

            const calculationReceivedAmount = async () => {
                if (!sendId && !receivedId && !sendCurrencyBuyRate && !receiveCurrencySellRate) {
                    return false;
                }
                const sendAmount = parseFloat(document.querySelector('#sending_amount_hidden').value)
                const receive_rate = await waitRateRight();
                const reciprocal = 1 / receive_rate
                const receiving_amount = reciprocal * sendAmount
                $('#receiving_amount_hidden').val(receiving_amount)
                $('#receiving_amount').val(receiving_amount.toFixed(receivingShowNumber))
            }

            const calculationSendAmount = async () => {
                if (!sendId && !receivedId && !sendCurrencyBuyRate && !receiveCurrencySellRate) {
                    return false;
                }
                const receivedAmount = document.querySelector('#sending_amount_hidden').value
                setTimeout(() => {
                    const sending_rate = $('.receive-rate-input').val()
                    const reciprocal = 1 / sending_rate
                    const sending_amount = reciprocal * receivedAmount
                    $('#sending_amount_hidden').val(sending_amount);
                    $('#sending_amount').val(sending_amount.toFixed(sendShowNumber));
                }, 100)
            }

        })(jQuery);
    </script>







@endpush

@push('style')
    <style>
        .select2-container .select2-selection--single {
            height: 46px;
            width: 100%;
        }

        .select2-search--dropdown {
            display: block !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
        }

        .select2-container--default img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        .select2-results__option--selectable {
            display: flex;
        }

        .select2-container .selection {
            width: 220px;
            height: 48px;
            -moz-border-radius: 0;
            border-radius: 0;
            position: absolute;
            right: 4px;
            top: 50%;
            padding: 0 10px;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            border-radius: 2px;
            background: #E8E8E8;
            border: 0 !important;
        }

        @media (max-width:1199px) {
            .select2-container .selection {
                width: 170px;
            }
        }

        @media (max-width:991px) {
            .select2-container .selection {
                width: 296px;
            }
        }

        @media (max-width:767px) {
            .select2-container .selection {
                width: 235px;
            }
        }

        @media (max-width:575px) {
            .select2-container .selection {
                width: 215px;
            }
        }

        @media (max-width:424px) {
            .select2-container .selection {
                transform: unset;
                position: relative;
                width: 100%;
                right: 0;
            }
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            top: 80%;
        }

        .select2-container--default .select2-results__option--disabled {
            display: none;
        }

        .select2-dropdown {
            border: 1px solid #aaaaaa2e;
        }

        img.currency-image {
            width: 25px;
            height: 25px;
            margin-right: 8px;
        }

        .select2-container--default .select2-selection--single {
            border: 0;
            background-color: transparent;
        }

        .select2-results__option:empty {
            display: none !important;
        }


        .select2-container--default .select2-selection--single .select2-selection__arrow:after {
            top: 4px !important;
        }

        .best-rate-slide {
            transition: all 0.3s ease-in-out;
            opacity: 0;
            transform: translateY(10px);
            display: none;
        }

        .best-rate-slide.show {
            opacity: 1;
            transform: translateY(0);
            display: block;
        }

        .best-rate-item {
            cursor: pointer;
        }


        /* style best rate list design  */

        .best-rate-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 14px;
        }

        .best-rate-list .list-group-item {
            position: relative;
            font-size: 0.875rem;
            background: #f2f2f2;
            padding: 7px 13px;
            border-radius: 5px;
        }

        .fw-600 {
            font-weight: 600;
        }

        .rate-flex-wrapper {
            display: flex;
            justify-content: space-between;
            /* width: 100%; */
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
        }

        .rate-flex-wrapper li {
            background: #f9f9f9;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 14px;
            list-style: none;
            /* width: 48%; */
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        /* Left align */
        .rate-left {
            text-align: left;
        }

        /* Right align */
        .rate-right {
            text-align: right;
        }

        .text--warning {
            color: #f59e0b;
            /* amber-500 */
            font-weight: 600;
        }

        .text--success {
            color: #10b981;
            /* green-500 */
            font-weight: 600;
        }

        /* Responsive: stack vertically on small screens */
        @media (max-width: 575px) {
            .rate-flex-wrapper li {
                /* width: 100%; */
                text-align: center;
            }
        }

        .rate-left input,
        .rate-right input {
            max-width: 120px;
        }
    </style>
@endpush