Settlements will not be processed on the following day(s) due to bank holidays:
@if($holidays)
<table class="container"><tr><th class="two columns"><span class="center">Date</span></th><th class="ten columns"><span class="center">Reason</span></th></tr>
@foreach ($holidays as $holiday)
<tr><td class="two columns"><span class="center">{{{$holiday['date']->toFormattedDateString()}}}</span></td><td class="ten columns"><span class="center">{{{$holiday['reason']}}}</span></td></tr>
@endforeach
</table>
@endif
<br><p><b>Settlements will next be processed on {{{$nextWorkingDayString}}}. </b></p>
<p>Thank you for partnering with Razorpay.</p>
