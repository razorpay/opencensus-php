@extends('admin.layoutGenerated')

@section('panelcontent')
@if(Session::has('status'))
    @foreach(Session::get('status') as $message)
        <div class="alert alert-info alert-dismissable">
        {{{$message}}}
        </div>
    @endforeach
@endif
      <h3>Plan ID: {{{$plan['id']}}}</h3>
      <h3>Plan Name: {{{$plan['name']}}}</h3>
      <table class = "table table-hover">
      <thead>
            <th>Rule ID</th>
            <th>Payment Mode</th>
            <th>Payment Mode Type</th>
            <th>Payment Network</th>
            <th>Payment Issuer</th>
            <th>Percent Rate</th>
            <th>Fixed Rate</th>
            <th>Created At</th>
      </thead>
      <tbody>
    @foreach($plan['rules'] as $rule)
      <tr>
            <td>{{{$rule['id']}}}</td>
            <td>{{{$rule['payment_mode']}}}</td>
            <td>{{{$rule['payment_mode_type']}}}</td>
            <td>{{{$rule['payment_network']}}}</td>
            <td>{{{$rule['payment_issuer']}}}</td>
            <td>{{{$rule['percent_rate']/100}}} %</td>
            <td>INR {{{$rule['fixed_rate'] /100}}}</td>
            <td>{{{$rule['created_at']}}}</td>            
      </tr>
    @endforeach
    </tbody>
    </table>

    <br/><br/>
      {{ Form::open() }}
            <h3>Add new Rule</h3>

            <label for="payment_mode">Payment Mode</label>
            <select name="payment_mode">
                  <option value="card" selected>Card</option>
            </select>

            <label for="payment_mode_type">Payment Mode Type</label>
            <select name="payment_mode_type">
                  <option value="credit">Credit</option>
                  <option value="debit">Debit</option>
                  <option value="">Both</option>
            </select>

            <label for="payment_network">Payment Network</label>
            <select name="payment_network">
                  <option value="">All</option>
                  <option value="VISA,MC,DICL,RP,MAES">VISA</option>
                  <option value="MC">MasterCard</option>
                  <option value="MAES">Maestro</option>
                  <option value="DICL">Diner's Club</option>
                  <option value="RP">Rupay</option>
            </select>

            <label for="payment_issuer">Payment Issuer</label>
            <select name="payment_issuer">
                  <option value="">All</option>
                  <option value="HDFC">HDFC</option>
            </select>
            <br/>
            <label for="percent_rate">Percentage Rate</label>
            <input type="text" name="percent_rate" placeholder="As integer" />
            <span class="help-text">Percent rate multiplied by 100 e.g. enter 2.89% as 289</span>
            <br/>
            <label for="fixed_rate">Fixed Rate</label>
            <input type="text" name="fixed_rate" placeholder="As Integer" />
            <span class="help-text">Fixed rate in paisa e.g. enter INR 2 as 200</span>
            <br/><br/>
            <button type="submit" class="btn btn-primary">Add Rule</button>

      {{ Form::close() }}
    </table>
@stop