Settlements will not be processed on the following days due to bank holidays:
@if($holidays)
<table cellpadding='5'>
    <tr>
        <th width='90'>Date</th><th width='290'>Reason</th>
    </tr>
    @foreach ($holidays as $holiday)
    <tr>
        <td width='90'>{{{$holiday['date']->toFormattedDateString()}}}</td>
        <td width='290'>{{{$holiday['reason']}}}</td>
    </tr>
    @endforeach
</table>
@endif
<br>
<p><b>Settlements will next be processed on {{{$nextWorkingDayString}}}.<b></p>
<p>Thank you for partnering with Razorpay.</p>
