<?php $absValue = abs($value); ?>
@if($value < 0)-
@endif
@if(isset($currency)){{{ $currency }}}@else₹
@endif
<?php echo number_format($absValue, '2', '.', '') ?>
