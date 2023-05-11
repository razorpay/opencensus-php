<?php
    $isOrgHDFC = (json_decode($org, true)['custom_code']) === "hdfc";
?>


@include('merchant/index1')
@include('merchant/index2')