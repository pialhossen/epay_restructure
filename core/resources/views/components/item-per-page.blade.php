<div class="row pl-2 pb-2 ml-2">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-0">
                @php
                $lastSegment = request()->segment(count(request()->segments()));
                @endphp
                <form action="{{ request()->url() }}" method="GET">
                    @foreach(request()->except('itemsPerPage') as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $subKey => $subValue)
                                <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <div class="row pb-2">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <select name="itemsPerPage" id="" class="form-control" style="width: 200px;">
                                    <option value="">Select Item Per page</option>
                                    <option value="5" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 5)>5</option>
                                    <option value="10" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 10)>10</option>
                                    <option value="20" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 20)>20</option>
                                    <option value="50" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 50)>50</option>
                                    <option value="100" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 100)>100</option>
                                    <option value="150" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 150)>150</option>
                                    <option value="200" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 200)>200</option>
                                    <option value="250" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 250)>250</option>
                                    <option value="500" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 500)>500</option>
                                    <option value="1000" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 1000)>1000</option>
                                    <option value="2000" @selected(isset(request()->query()['itemsPerPage']) && request()->query()['itemsPerPage'] == 2000)>2000</option>
                            </select>
                            <button type="Submit" class="btn btn-sm btn-primary h-45">Apply</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>