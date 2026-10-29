<div class="box box-solid" style="border-top: 3px solid #3c8dbc; margin-bottom: 15px;">
    <div class="box-body">
        <form method="post" action="{{ url('accounting/transactions/map_category') }}">
            @csrf
            <input type="hidden" name="map_type" value="{{ $map_type }}">
            <input type="hidden" name="mapping_for" value="{{ $mapping_for }}">
            <div class="row">
                <div class="col-md-3">
                    <strong>{{ $map_label }} account</strong>
                    @if (!empty($linked_account_name))
                        <div><span class="label label-success">{{ $linked_account_name }}</span></div>
                    @endif
                </div>
                <div class="col-md-5">
                    <select name="chart_of_account_id" class="form-control select2" style="width: 100%;" required>
                        <option value="">Select account</option>
                        @foreach ($chart_of_accounts as $account)
                            @php
                                $isSelected = !empty($linked_account_id) && (int) $linked_account_id === (int) $account->id;
                                $glCode = !empty($account->gl_code) ? ' ('.$account->gl_code.')' : '';
                            @endphp
                            <option value="{{ $account->id }}" {{ $isSelected ? 'selected' : '' }}>{{ $account->name }}{{ $glCode }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Map all</button>
                </div>
            </div>
            <p class="help-block" style="margin: 10px 0 0;">Every {{ $map_label }} uses this account and is added to Journal Entries. The newest one stays at the top.</p>
        </form>
    </div>
</div>
