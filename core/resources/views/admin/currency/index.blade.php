@extends('admin.layouts.app')
@section('panel')
<script>
    let order_array = []
    function updateOrder(e){
        e.preventDefault();
        const form = document.querySelector('#order-form')
        const inputElement = form.querySelector('#order')
        inputElement.value = order_array
        console.log(inputElement)
        form.submit()
    }
</script>
    <x-item-per-page/>
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th style="cursor: pointer;" onclick="toggleSort(event, 'name')">
                                        <div class="sortable-header">
                                            <span class="sort-indicate"> 
                                                <span class="up" @if(str_contains(request()->query("sort"),'name')) style="visibility: {{ request()->query("sort") == "name:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                                <span class="down" @if(str_contains(request()->query("sort"),'name')) style="visibility: {{ request()->query("sort") == "name:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                                            </span> 
                                            <span class="text">
                                                @lang('Currency')
                                            </span>
                                        </div>
                                    </th>
                                    <th style="cursor: pointer;" onclick="toggleSort(event, 'buy_at')">
                                        <div class="sortable-header">
                                            <span class="sort-indicate"> 
                                                <span class="up" @if(str_contains(request()->query("sort"),'buy_at')) style="visibility: {{ request()->query("sort") == "buy_at:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                                <span class="down" @if(str_contains(request()->query("sort"),'buy_at')) style="visibility: {{ request()->query("sort") == "buy_at:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                                            </span> 
                                            <span class="text">
                                                @lang('Buy At')
                                            </span>
                                        </div>
                                    </th>
                                    <th style="cursor: pointer;" onclick="toggleSort(event, 'sell_at')">
                                        <div class="sortable-header">
                                            <span class="sort-indicate"> 
                                                <span class="up" @if(str_contains(request()->query("sort"),'sell_at')) style="visibility: {{ request()->query("sort") == "sell_at:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                                <span class="down" @if(str_contains(request()->query("sort"),'sell_at')) style="visibility: {{ request()->query("sort") == "sell_at:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                                            </span> 
                                            <span class="text">
                                                @lang('Sell At')
                                            </span>
                                        </div>
                                    </th>
                                    <th style="cursor: pointer;" onclick="toggleSort(event, 'reserve')">
                                        <div class="sortable-header">
                                            <span class="sort-indicate"> 
                                                <span class="up" @if(str_contains(request()->query("sort"),'reserve')) style="visibility: {{ request()->query("sort") == "reserve:desc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-up"></i></span> 
                                                <span class="down" @if(str_contains(request()->query("sort"),'reserve')) style="visibility: {{ request()->query("sort") == "reserve:asc"? "visible": "hidden" }};" @endif><i class="fa-solid fa-arrow-down"></i></span> 
                                            </span> 
                                            <span class="text">
                                                @lang('Reserve Amount')
                                            </span>
                                        </div>
                                    </th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody id="sortable">
                                @forelse($currencies as $currency)
                                    <tr data-id="{{ $currency->id }}">
                                        
                                        <td>
                                            <div class="user">
                                                <div class="thumb">
                                                    <img
                                                        src="{{ getImage(getFilePath('currency') . '/' . $currency->image, getFileSize('currency')) }}">
                                                </div>
                                                <span class="name">
                                                    {{ __($currency->name) }} - {{ __($currency->cur_sym) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ showAmount($currency->buy_at) }}</td>
                                        <td>{{ showAmount($currency->sell_at) }}</td>
                                        <td>
                                            {{ showAmount($currency->reserve, currencyFormat: false) }}
                                            {{ $currency->cur_sym }}
                                        </td>

                                        <td> @php echo $currency->statusBadge; @endphp </td>
                                        <td>
                                            @if(checkSpecificPermission('View - Currency Details') || checkSpecificPermission('View - Transaction Proof Form') || checkSpecificPermission('View - Sending Form') || checkSpecificPermission('View - Disable/Enable'))
                                            <button aria-expanded="false" class="btn btn-outline--primary btn-sm"
                                                data-bs-toggle="dropdown" type="button">
                                                <i class="las la-ellipsis-v"></i>@lang('More')
                                            </button>
                                            <div class="dropdown-menu">
                                                @if(checkSpecificPermission('View - Currency Details'))
                                                <a href="{{ route('admin.currency.edit', $currency->id) }}"
                                                    class="dropdown-item">
                                                    <i class="la la-pencil"></i> @lang('Edit')
                                                </a>
                                                @endif
                                                @if(checkSpecificPermission('View - Transaction Proof Form'))
                                                <a href="{{ route('admin.currency.transaction.proof.form', $currency->id) }}"
                                                class="dropdown-item">
                                                    <i class="lab la-wpforms"></i> @lang('Transaction Proof Form')
                                                </a>
                                                @endif
                                                @if(checkSpecificPermission('View - Sending Form'))
                                                <a href="{{ route('admin.currency.sending.form', $currency->id) }}"
                                                class="dropdown-item">
                                                <i class="las la-paste"></i> @lang('Sending Form')
                                                </a>
                                                @endif
                                                @if(checkSpecificPermission('View - Disable/Enable'))
                                                @if ($currency->status == Status::DISABLE)
                                                
                                                    <button class="dropdown-item confirmationBtn"
                                                        data-action="{{ route('admin.currency.status', $currency->id) }}"
                                                        data-question="@lang('Are you sure to enable this currency?')">
                                                        <i class="la la-eye"></i> @lang('Enable')
                                                    </button>
                                                @else
                                                    <button class="dropdown-item  confirmationBtn"
                                                        data-action="{{ route('admin.currency.status', $currency->id) }}"
                                                        data-question="@lang('Are you sure to disable this currency?')">
                                                        <i class="la la-eye-slash"></i> @lang('Disable')
                                                    </button>
                                                @endif
                                                @endif
                                            </div>
                                            @else
                                            <button aria-expanded="false" class="btn btn-outline--primary btn-sm"
                                                data-bs-toggle="dropdown" type="button" disabled>
                                                <i class="las la-ellipsis-v"></i>@lang('More')
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                     new Sortable(document.getElementById('sortable'), {
                        animation: 150,
                        onEnd: function (evt) {
                        // When a row is dropped
                        order_array = Array.from(document.querySelectorAll('#sortable tr')).map(
                            tr => tr.dataset.id
                        );

                        const orderSubmit = document.querySelector('.order-submit')
                        orderSubmit.style.display = "block"
                        }
                    });
                </script>
                
                
                <div class="order-submit py-4" style="display: none;">
                    <form action="{{ route('admin.currency.saveOrder', request()->query()) }}" id="order-form" method="post">
                        @csrf
                        <input type="hidden" name="order" id="order">
                        <button type="submit" onclick="updateOrder(event)" class="btn btn--primary w-100 h-45" value="add">
                            @lang('Submit')
                        </button>
                    </form>
                </div>

                <div class="card-footer py-4">
                    @if ($currencies->hasPages())
                        {{ paginateLinks($currencies) }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-confirmation-modal />

    <div class="modal fade" id="currencyApiModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="currencyApiModalLabel">@lang('Currency API Key')</h4>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i></button>
                </div>
                <form action="{{ route('admin.currency.api.update') }}" method="post" class="disableSubmission">
                    @csrf
                    <div class="modal-body">
                        <div class="row form-group">
                            <div class="justify-content-between d-flex flex-wrap">
                                <label>@lang('Currency Rate API Key')</label>
                                <div>
                                    <small>@lang('For the API key') : </small>
                                    <u>
                                        <a target="_blank" class="text--primary" href="https://app.exchangerate-api.com">
                                            @lang('Exchange Rate API')
                                        </a>
                                    </u>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <input class="form-control" type="text" name="api_key" required
                                    value="{{ gs('currency_api_key') }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45" id="btn-save" value="add">
                            @lang('Submit')
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form />
    <a class="btn btn-outline--primary" href="{{ route('admin.currency.create') }}">
        <i class="las la-plus"></i>@lang('Add New')
    </a>
    <button class="btn btn-outline--dark h-45 me-2" data-bs-toggle="modal" data-bs-target="#currencyApiModal">
        <i class="las la-key"></i>@lang('Currency API Key')
    </button>
@endpush
