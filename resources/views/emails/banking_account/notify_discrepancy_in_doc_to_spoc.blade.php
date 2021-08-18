<!doctype html>
<html lang="en">
<head>
    <title></title>
    <style>
        table, th, td {
            border: 1px solid black;
        }
    </style>
</head>
<body>
<br/> Hi,<br/>
<p>The following customers are either stuck or taking more than the expected time to provide valid documents required for the
    Current Account opening process. We need your attention to make sure the customers complete the CA onboarding
    journey.</p>
<br/>
<br/>
<table>
    <tr>
        <td>Business Name</td>
        <td>Merchant ID</td>
        <td>Merchant POC Name</td>
        <td>Merchant POC Phone</td>
        <td>LMS Link</td>
    </tr>
    @foreach ($data as $user)
        <tr>
            <td>{{$user['businessName']}}</td>
            <td>{{$user['merchant_id']}}</td>
            <td>{{$user['name']}}</td>
            <td>{{$user['phoneNumber']}}</td>
            <td><a href={{$user['lmsLink']}}>LMS link</a></td>
        </tr>
    @endforeach
</table>
<br/>
<br/>
Thanks,<br/>
Team RazorpayX<br/>
</body>
</html>
