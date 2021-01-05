<!doctype html>
<html>
<head>
  <title>Confirm Mandate</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <style>
      svg {
        position: fixed;
        top: 0;
        left: 0;
      }
      .image-center{
        display:block;
        margin:auto;
      }
      .text-center{
        text-align: center;
      }
      .dblock{
        display: block;
      }
      body {
        font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Open Sans','Helvetica Neue',sans-serif;
        font-size: 14px;
        color: #777;
      }
      .pb15{
        padding-bottom:15px;
      }
      .text-right{
          text-align: right;
      }

      .rzp-logo{
        display: block;
    margin: auto;
    margin-top: 22px;
      }
      main {
        position: relative;
        margin: 60px auto;
        box-shadow: 0 2px 8px rgba(0,0,0,0.14);
        background: #fff;
        padding: 30px;
        max-width: 600px;
        box-sizing: border-box;
        font-weight: bold;
        text-transform: uppercase;
      }
      .fields{
          width: 50%;
          padding-bottom:15px;
          float:left;
      }
      .clearfix{
          clear: both;
      }
      footer {
        max-width: 600px;
        margin: 30px auto;
        font-size: 12px;
        opacity: 0.9;
      }
      header {
        display: flex;
      }
      header div {
        flex: 1;
        text-align: center;
        font-weight: bold;
        font-size: 16px;
      }
      section {
        margin: 40px 10px 0;
      }
      .name {
        overflow: hidden;
        white-space: nowrap;
        position: relative;
      }
      .name:after {
        content: '';
        width: 100%;
        position: absolute;
        border-top: 1px solid #ccc;
        margin-left: 20px;
        top: 8px;
      }
      .bank {
        margin-top: 20px;
      }
      .bank img {
        float: left;
        margin: 6px 10px 0 0;
      }
      .acc {
        color: #111;
        font-size: 18px;
      }
      .flex {
        margin-top: 20px;
      }
      .mandate {
        background: #F6FAFF;
        margin: 40px -40px;
        border: 1px solid #B6D4FF;
        padding: 20px;
        font-size: 12px;
      }
      .flex span {
        display: block;
        color: #444;
        margin-top: 4px;
        text-transform: none;
        font-size: 16px;
      }
      .mandate :first-child span {
        white-space: nowrap;
      }
      button {
        background: #528FF0;
        height: 46px;
        border-radius: 2px;
        margin-left: 20px;
        padding: 0 22px;
        border: 0;
        color: #fff;
        font-size: 16px;
        cursor: pointer;
      }
      button:disabled {
        opacity: 0.5;
      }
    </style>
</head>
<body>
<svg width="100%" height="300px" viewBox="0 0 12 6" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
  <defs><linearGradient id="grad">
    <stop offset="0" stop-color="#246EC8"/>
    <stop offset="1" stop-color="#4A9DDF"/>
  </linearGradient></defs>
  <polygon points="0,0 0,4 1,5 12,1" fill="#F4F8FF" />
  <polygon points="0,0 0,2 10,6 12,0" fill="url(#grad)" />
</svg>
<main>
  <header>
    <img height="32" src="https://cdn.razorpay.com/brand/npci.png">
    <div>MANDATE SUMMARY</div>
    <img height="24" src="https://cdn.razorpay.com/brand/nach.png">
  </header>
  <section>
    <div class="name">{{ htmlspecialchars($data['emandate_details']['customer_name']) }}</div>
    <div class="bank">
      <img height="30" src="https://cdn.razorpay.com/bank/{{ htmlspecialchars($data['emandate_details']['bank']) }}.gif">
      ACCOUNT NUMBER
      <div class="acc">{{ htmlspecialchars($data['emandate_details']['account_number']) }}</div>
    </div>
  </section>
  <div class="mandate flex">
    <div class="fields">MAX AMOUNT<span>₹ {{ htmlspecialchars($data['emandate_details']['max_amount']) }}</span></div>
    <div class="fields text-right">START DATE<span>{{ htmlspecialchars($data['emandate_details']['mandate_start_date']) }}</span></div>
    <div class="fields ">END DATE<span>{{ htmlspecialchars($data['emandate_details']['mandate_end_date']) }}</span></div>
    <div class="fields text-right">FREQUENCY<span>{{ htmlspecialchars($data['emandate_details']['frequency']) }}</span></div>
    <div class="clearfix"></div>
  </div>
  <section>
    <div class="name">CORPORATE INFORMATION</div>
    <div class="flex">
      <div class="pb15">NAME<span>{{ htmlspecialchars($data['emandate_details']['corporate_name']) }}</span></div>
      <div class="pb15">UTILITY CODE<span>{{ htmlspecialchars($data['emandate_details']['utility_code']) }}</span></div>
      <div class="pb15">PURPOSE<span>{{ htmlspecialchars($data['emandate_details']['purpose_text']) }}</span></div>
    </div>
  </section>
  @yield('content')
</main>
<footer>
    <img class="image-center" height="20" src="https://cdn.razorpay.com/static/assets/upi_visa_mc_ae_pc.png">
    <br>
    <span class="dblock text-center">Accept, process and disburse digital payments for your business.
    <a href="https://razorpay.com" target="_blank">Know More.</a>
    </span>

    <div>
        <img class="rzp-logo" height="26" src="https://cdn.razorpay.com/logo.svg">
    </div>
  </footer>
<form id="form2" name="form2">
  @if (isset($data['type']))
  <input type="hidden" name="type" value="{{$data['type']}}">
  @endif
  @if (isset($data['gateway']))
  <input type="hidden" name="gateway" value="{{$data['gateway']}}">
  @endif
</form>
</body>
