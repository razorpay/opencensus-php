<?php $absValue = abs($value); ?>
@if($value < 0)-@endif
@if(isset($currency)){{{ $currency/(isSet($isRupees) ? 1 : 100) }}}@else₹@endif
{{$absValue}}
