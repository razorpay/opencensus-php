<?php $absValue = abs($value); ?>
@if($value < 0)-
@endif
@if(isset($currency)){{{ $currency }}}@else₹
@endif
{{$absValue/(isSet($isRupees) ? 1 : 100)}}
