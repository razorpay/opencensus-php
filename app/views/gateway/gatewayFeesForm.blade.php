<!doctype html>
<html lang="en" style="height: 100%; overflow: hidden;font-family:ubuntu,helvetica,sans-serif;">
    <form id="form1" name="form1">
    <div>
        <label for="originalAmount">Amount</label>
        <input disabled name="originalAmount" value="{{$data['originalAmount']}}"></input>
    </div>
    <div>
        <label for="fees">Fees (inclusive of Service Tax)</label>
        <input disabled name="fees" value="{{$data['fees']}}">
    </div>
    <div>
        <label for="serviceTax">Service Tax Charged</label>
        <input disabled name="serviceTax" value="{{$data['serviceTax']}}">
    </div>
    <div>
        <label for="amount">Total Amount</label>
        <input disabled name="amount" value="{{$data['amount']}}"></input>
    </div>
    </form>
    </body>
</html>
