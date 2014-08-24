@extends('admin.layoutGenerated')

@section('panelcontent')
@if(Session::has('status'))
    @foreach(Session::get('status') as $message)
        <div class="alert alert-info alert-dismissable">
        {{{$message}}}
        </div>
    @endforeach
@endif
      <h2>New Pricing Plan</h2>
      {{ Form::open() }}
        <br/>
            <label for "plan_name">Plan Name:</label>
            <input type="text" class="form-control" name="plan_name" placeholder="Plan Name" required autofocus>
        <br/><br/>
            <h3>Add first Rule</h3>

            <label for="payment_mode">Payment Mode</label>
            <select name="payment_mode" required>
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
                  <option value="VISA">VISA</option>
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
            <input type="text" name="percent_rate" placeholder="As integer" required/>
            <span class="help-text">Percent rate multiplied by 100 e.g. enter 2.89% as 289</span>
            <br/>
            <label for="fixed_rate">Fixed Rate</label>
            <input type="text" name="fixed_rate" placeholder="As Integer" required/>
            <span class="help-text">Fixed rate in paisa e.g. enter INR 2 as 200</span>
            <br/><br/>
            <button type="submit" class="btn btn-primary">Create</button>

      {{ Form::close() }}
    </table>
@stop