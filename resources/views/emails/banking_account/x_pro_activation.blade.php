<br/>
{{$merchant_name}} has requested current account functionality on RazorpayX. <br/>
<br/>
<b>Merchant Details below:- </b> <br/>
<br/>
- Razorpay Reference Number : {{$internal_reference_number}} <br/>
- Merchant Name : {{$merchant_name}} <br/>
- Merchant ID: {{$merchant_id}} <br/>
- Merchant Email : {{$merchant_email}} <br/>
- Pin Code : {{$pincode}} <br/>
- Business Category: {{$business_category}} <br/>
- Application Date: {{$application_date}} <br/>
- Sales Team: {{$sales_team}} <br/>
- SPOC Email: {{$sales_poc_email}} <br/>
- Green Channel: {{$green_channel}} <br/>
@isset($skip_dwt_status)
- Skip DWT Status: {{$skip_dwt_status}} <br/>
@endisset
@isset($docket_address_different_from_registered_address)
- Docket Address different from Registered Address : {{$docket_address_different_from_registered_address}} <br/>
@endisset
@if ($sales_team === 'self_serve')
    <br/><b>Self Serve Fields below:- </b> <br/>
    - Slot Booked Date & Time: {{$slot_booking_date_and_time}} <br/>
    - Assigned Ops Reviewer: {{$reviewer_name}} <br/>
@endif
