@php
    function getPercentValue($percent, $value)
    {
        $percent = (float) $percent;
        $value = (float) $value;
        return ($percent / 100) * $value;
    }

    $title = 'title';
    //$title = 'description';
@endphp
<div class="col-md-6">
    <div class="card custom--card">
        <div class="card-header">
            <h5 class="card-title">@lang('Sending Details')</h5>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush p-3">
                <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                    <div class="d-flex align-items-center gap-2">
                        <span class="svg__icon">
                            <x-method-icon />
                        </span>
                        <small class="fw-bold">@lang('Method')</small>
                    </div>
                    <span class="d-flex align-items-center">
                        <div class="thumb me-2">
                            <img class="currency__image"
                                src="{{ getImage(getFilePath('currency') . '/' . @$exchange->sendCurrency->image, getFileSize('currency')) }}"
                                alt="currency image">
                        </div>
                        <span class="fw-bold">{{ __(@$exchange->sendCurrency->name) }}</span>
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                    <div class="d-flex align-items-center gap-2">
                        <span class="svg__icon">
                            <x-currency-icon />
                        </span>
                        <small class="fw-bold">@lang('Currency')</small>
                    </div>
                    <span class="fw-bold">{{ __(ucfirst(@$exchange->sendCurrency->cur_sym)) }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                    <div class="d-flex align-items-center gap-2">
                        <span class="svg__icon">
                            <x-amount-icon />
                        </span>
                        <small class="fw-bold">@lang('Amount')</small>
                    </div>
                    <span class="fw-bold">
                        {{ number_format(@$exchange->sending_amount, @$exchange->sendCurrency->show_number_after_decimal) }}
                        {{ __(ucfirst(@$exchange->sendCurrency->cur_sym)) }}
                    </span>
                </li>

                @if($charges && $exchange->status != "0")
                @if(isset($charges['sell']['percent']))
                @foreach ($charges['sell']['percent'] as $index => $charge)
                    <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                        <div class="d-flex align-items-center gap-2">
                            <span class="svg__icon">
                                <x-charge-icon />
                            </span>
                            <small class="fw-bold">@lang($charge[$title])</small>
                        </div>
                        <span
                            class="fw-bold @if($charge['charge_percent'] > 0) text--danger @else text--success-dim @endif">
                            {{ number_format(getPercentValue($charge['charge_percent'], $exchange->sending_amount), @$exchange->sendCurrency->show_number_after_decimal) }}
                            {{ __(ucfirst(@$exchange->sendCurrency->cur_sym)) }}
                        </span>
                    </li>
                @endforeach
                @endif
                @if(isset($charges['sell']['fixed']))
                @foreach ($charges['sell']['fixed'] as $index => $charge)
                    <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                        <div class="d-flex align-items-center gap-2">
                            <span class="svg__icon">
                                <x-charge-icon />
                            </span>
                            <small class="fw-bold">@lang($charge[$title])</small>
                        </div>
                        <span
                            class="fw-bold @if($charge['charge_fixed'] > 0) text--danger @else text--success-dim @endif">
                            {{ number_format($charge['charge_fixed'], @$exchange->sendCurrency->show_number_after_decimal) }}
                            {{ __(ucfirst(@$exchange->sendCurrency->cur_sym)) }}
                        </span>
                    </li>
                @endforeach
                @endif
                @endif
                <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                    <div class="d-flex align-items-center gap-2">
                        <span class="svg__icon">
                            <x-charge-icon />
                        </span>
                        <small class="fw-bold">@lang('Total Charge / Discount')</small>
                    </div>
                    @if($exchange->sending_charge == 0)
                    <span
                        class="fw-bold">
                        {{ number_format(@$exchange->sending_charge, @$exchange->sendCurrency->show_number_after_decimal) }}
                        {{ __(ucfirst(@$exchange->sendCurrency->cur_sym)) }}
                    </span>
                    @else
                    <span
                        class="fw-bold @if(@$exchange->sending_charge > 0) text--danger @else text--success-dim @endif">
                        {{ number_format(@$exchange->sending_charge, @$exchange->sendCurrency->show_number_after_decimal) }}
                        {{ __(ucfirst(@$exchange->sendCurrency->cur_sym)) }}
                    </span>
                    @endif
                </li>


                <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                    <div class="d-flex align-items-center gap-2">
                        <span class="svg__icon">
                            <x-total-icon />
                        </span>
                        <small class="fw-bold">@lang('Total Sending Amount Including Charge')</small>
                    </div>
                    <span class="fw-bold">
                        {{ number_format($exchange->sending_amount + $exchange->sending_charge, @$exchange->sendCurrency->show_number_after_decimal) }}
                        {{ __(ucfirst(@$exchange->sendCurrency->cur_sym)) }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
    </div>
    <div class="col-md-6">
    <div class="card custom--card">
        <div class="card-header">
            <h5 class="card-title">@lang('Receiving Details')</h5>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush p-3">
                <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                    <div class="d-flex align-items-center gap-2">
                        <span class="svg__icon">
                            <x-method-icon />
                        </span>
                        <small class="fw-bold">@lang('Method')</small>
                    </div>
                    <span class="d-flex align-items-center">
                        <div class="thumb me-2">
                            <img class="currency__image"
                                src="{{ getImage(getFilePath('currency') . '/' . @$exchange->receivedCurrency->image, getFileSize('currency')) }}"
                                alt="currency image">
                        </div>
                        <span class="fw-bold">{{ __(@$exchange->receivedCurrency->name) }}</span>
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                    <div class="d-flex align-items-center gap-2">
                        <span class="svg__icon">
                            <x-currency-icon />
                        </span>
                        <small class="fw-bold">@lang('Currency')</small>
                    </div>
                    <span class="fw-bold">
                        {{ __(ucfirst(@$exchange->receivedCurrency->cur_sym)) }}
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                    <div class="d-flex align-items-center gap-2">
                        <span class="svg__icon">
                            <x-amount-icon />
                        </span>
                        <small class="fw-bold">@lang('Amount')</small>
                    </div>
                    <span class="fw-bold">
                        {{ number_format(@$exchange->receiving_amount, $exchange->receivedCurrency->show_number_after_decimal) }}
                        {{ __(ucfirst(@$exchange->receivedCurrency->cur_sym)) }}
                    </span>
                </li>
                @if($charges && $exchange->status != "0")
                @if(isset($charges['buy']['percent']))
                @foreach ($charges['buy']['percent'] as $index => $charge)
                    <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                        <div class="d-flex align-items-center gap-2">
                            <span class="svg__icon">
                                <x-charge-icon />
                            </span>
                            <small class="fw-bold">@lang($charge[$title]) </small>
                        </div>
                        <span
                            class="fw-bold @if($charge['charge_percent'] > 0) text--success-dim @else text--danger @endif">
                            {{ -1 * number_format(getPercentValue($charge['charge_percent'], $exchange->receiving_amount), $exchange->receivedCurrency->show_number_after_decimal) }}
                            {{ __(ucfirst(@$exchange->receivedCurrency->cur_sym)) }}
                        </span>
                    </li>
                @endforeach
                @endif
                @if(isset($charges['buy']['fixed']))
                @foreach ($charges['buy']['fixed'] as $index => $charge)
                    <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                        <div class="d-flex align-items-center gap-2">
                            <span class="svg__icon">
                                <x-charge-icon />
                            </span>
                            <small class="fw-bold">@lang($charge[$title]) </small>
                        </div>
                        <span
                            class="fw-bold @if($charge['charge_fixed'] > 0) text--success-dim @else text--danger @endif">
                            {{ -1 * number_format($charge['charge_fixed'], $exchange->receivedCurrency->show_number_after_decimal) }}
                            {{ __(ucfirst(@$exchange->receivedCurrency->cur_sym)) }}
                        </span>
                    </li>
                @endforeach
                @endif
                @endif

                <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                    <div class="d-flex align-items-center gap-2">
                        <span class="svg__icon">
                            <x-charge-icon />
                        </span>
                        <small class="fw-bold">@lang('Total Charge / Discount')</small>
                    </div>
                    @if($exchange->receiving_charge == 0)
                    <span class="fw-bold">
                        {{ number_format(@$exchange->receiving_charge, $exchange->receivedCurrency->show_number_after_decimal) }}
                        {{ __(ucfirst(@$exchange->receivedCurrency->cur_sym)) }}
                    </span>
                    @else
                    <span class="fw-bold @if(isset($charge) && $charge['charge_percent'] > 0) text--success-dim @else text--danger @endif">
                        {{ -1 * number_format(@$exchange->receiving_charge, $exchange->receivedCurrency->show_number_after_decimal) }}
                        {{ __(ucfirst(@$exchange->receivedCurrency->cur_sym)) }}
                    </span>
                    @endif
                </li>

                <li class="list-group-item d-flex justify-content-between flex-wrap border-dotted">
                    <div class="d-flex align-items-center gap-2">
                        <span class="svg__icon">
                            <x-total-icon />
                        </span>
                        <small class="fw-bold">@lang('Receivable Amount After Charge')</small>
                    </div>
                    <span class="fw-bold">
                        {{ number_format($exchange->receiving_amount - $exchange->receiving_charge, $exchange->receivedCurrency->show_number_after_decimal) }}
                        {{ __(ucfirst(@$exchange->receivedCurrency->cur_sym)) }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
</div>