<!doctype html>
<html>
<head>
    <title>Processing, Please wait...</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Ubuntu', 'Cantarell', 'Droid Sans', 'Helvetica Neue', sans-serif;
            background: #f4f4f4;
            color: #414141;
            font-size: 14px;
            line-height: 1.6;
        }
        .container {
            width: 92%;
            max-width: 330px;
            margin: 0 auto 20px;;
        }
        form.main {
            margin: 20px auto 0px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 7px rgba(0,0,0,0.09);
        }
        header {
            border-bottom: 1px solid #eee;
            line-height: 64px;
            padding: 0 20px;
            text-align: right;
            font-size: 22px;
        }
        main {
            padding: 20px;
        }
        label {
            display: block;
            margin: 20px 0 6px;
        }
        input {
            border: 1px solid #bbb;
            box-shadow: 0 2px 3px rgba(0,0,0,0.1) inset;
            border-radius: 2px 0 0 2px;
            height: 40px;
            outline: none;
            width: 100%;
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
            padding: 0 16px;
            font-size: 14px;
            font-family: inherit;
            color: #111;
        }
        button:disabled {
            opacity: .5;
            cursor: not-allowed !important;
        }
        button:not(#cancel-btn) {
            display: block;
            margin: 20px auto 0;
            height: 42px;
            border: 1px solid #3395FF;
            background: #3395FF;
            border-radius: 0 2px 2px 0;
            color: #fff;
            padding: 0 20px;
            cursor: pointer;
            outline: none;
        }
        span {
            position: absolute;
            line-height: 42px;
            margin-left: 16px;
            pointer-events: none;
        }
        input[type=tel] {
            padding-left: 58px;
        }

        #cancel-btn {
            color: #d66;
            border: none;
            border-bottom: 1px dashed #faa;
            background-color: transparent;
            font-size: 13px;
            cursor: pointer;
            margin: 16px 0;
        }

        #cancel-btn:before {
            font-size: 13px;
            float: left;
            content: '<';
            transform: scale(0.7, 1.3);
            margin: 0 3px 0 -3px;
        }
    </style>
</head>
<body>
<img src="{{$data['cdn']}}/logo.svg" id="logo" height="35px" style="margin:30px auto 10px; display:block">
<form action="{{ $data['request']['url'] }}" method="{{ $data['request']['method'] }}" class="container main">
    @foreach ($data['request']['content'] as $key => $value)
    @if (is_array($value))
    @foreach ($value as $key2=>$value2)
    <input type='hidden' name='{{$key}}[{{$key2}}]' value='{{$value2}}'>
    @endforeach
    @else
    <input type='hidden' name='{{$key}}' value='{{$value}}'>
    @endif
    @endforeach
    <header>
        <img src="https://cdn.razorpay.com/{{ $data['method'] }}/{{ $data['provider'] }}.svg" style='float: left;height: 30px;margin: 17px 0;'>
        <div>&#8377; {{ $data['request']['content']['amount']/100 }}</div>
    </header>
    <main>

        @if (((empty($data['provider'])=== false) and ($data['provider'] === 'icic')) and ((empty($data['method'])=== false) and ($data['method'] === 'paylater')))
        Please enter your ICICI bank payLater mobile number.
        @else
        Please enter your contact details to proceed.
        @endif
        @if ((empty($data['missing']) === true) or (in_array('email' , $data['missing']) === true))
        <label for='email'><b>Email</b></label>
        <input
            name='email'
            autofocus
            type='email'
            required
            placeholder='Enter Email'
            pattern="^[^@\s]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$"
            value={{ $data['request']['content']['email'] ?? "" }}>
        @endif
        @if ((empty($data['missing']) === true) or (in_array('contact' , $data['missing']) === true))
        <label for='contact'>
            <b>Contact</b>
            (10 digit Indian number)
        </label>
        <span>+91 &ndash;</span>
        <input type="hidden" id="hidden-contact">
        <input
            name='contact'
            type='tel'
            pattern='^\d{10}$'
            required
            placeholder='Enter Phone Number'
            value={{ $data['request']['content']['contact'] ?? "" }}>
        @endif
        <button id="submit-btn">Submit</button>
    </main>
</form>
@if (isset($data['request']['content']['callback_url']))
<form action="{{ $data['request']['content']['callback_url'] }}" method="post" class="container">
    <input name="error[description]" value="Payment processing cancelled by user" type="hidden">
    <input name="error[code]" value="BAD_REQUEST_ERROR" type="hidden" >
    <button id="cancel-btn" type="submit">Cancel Payment</button>
</form>
@endif
</body>
</html>
<script>
    document.forms[0].onsubmit = function(e) {
        e.preventDefault();
        if (this.contact) {
            var hiddenContact = this.querySelector('#hidden-contact');
            hiddenContact.value = '+91' + this.contact.value;
            this.contact.removeAttribute('name');
            hiddenContact.name = 'contact';
        }
        var submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerText = 'Loading...';
        this.submit();
    }
</script>
