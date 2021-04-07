<br>
Hi Team,

<p>We have received the CA account opening webhook data from RBL bank. Looks like the business name and pincode in the webhook data are not matching with the Razorpay details. Please verify the other details below and notify the bank if the details are completely incorrect.</p>

<p>Merchant ID: {{$merchantId}}</p>
<p>RZP Reference No.: {{$bankReferenceNumber}} </p>

<table>
    <tr>
        <th>Fields</th>
        <th>Razorpay Details</th>
        <th>RBL Webhook Details</th>
    </tr>

    <tr>
        <td>Business Name</td>
        <td>{{$razorpayDetails['businessName']}}</td>
        <td>{{$rblWebhookDetails['businessName']}}</td>
    </tr>

    <tr>
        <td>Business Pincode</td>
        <td>{{$razorpayDetails['pinCode']}}</td>
        <td>{{$rblWebhookDetails['pinCode']}}</td>
    </tr>

    <tr>
        <td>Business Address</td>
        <td>{{$razorpayDetails['businessCity']}}</td>
        <td>{{$rblWebhookDetails['businessCity']}}</td>
    </tr>

    <tr>
        <td>Business City</td>
        <td>{{$razorpayDetails['businessAddress']}}</td>
        <td>{{$rblWebhookDetails['businessAddress']}}</td>
    </tr>

    <tr>
        <td>Bank Reference Number</td>
        <td>{{$razorpayDetails['bankReferenceNumber']}}</td>
        <td>{{$rblWebhookDetails['bankReferenceNumber']}}</td>
    </tr>

    <tr>
        <td>Email</td>
        <td>{{$razorpayDetails['email']}}</td>
        <td>{{$rblWebhookDetails['email']}}</td>
    </tr>

    <tr>
        <td>Phone Number</td>
        <td>{{$razorpayDetails['phoneNumber']}}</td>
        <td>{{$rblWebhookDetails['phoneNumber']}}</td>
    </tr>

</table>
<br/>
<p>Thanks!</p>
