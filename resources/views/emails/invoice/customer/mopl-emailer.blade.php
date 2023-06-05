<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html lang="en">
    @php
        $ctaHref = $invoice['short_url'];
    @endphp
    <head>
        <title>Missed Order Payment Link - Razorpay</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <style>
            /*Responsive mobile style*/
            body {
                margin: auto;
            }
            .layout {
                position: absolute; 
                top: 26%; 
                left: 15%; 
                width: 76%;
                @media screen and (max-width: 600px) {
                    top: 24%; 
                    left: 14%; 
                    width: 75%;
                }
            }
            .mobile-btn {
                background: radial-gradient(50% 50% at 50% 50%, #000053 0%, #0000A8 100%); 
                border-radius: 4px;
                width: 337px; 
                margin: 40px 0 0 20px;
                @media screen and (max-width: 600px) {
                    width: 130px;
                    height: 28px;
                    margin: 22px 0 0 0;
                }
            }  
            .heading {
                color: #081265; 
                text-align: center; 
                line-height: 41px; 
                font-weight: 600; 
                font-family: Arial, -apple-system, BlinkMacSystemFont, sans-serif; 
                font-size: 28px; 
                @media screen and (max-width: 600px) {
                    margin: 0;
                    font-size: 13px;
                    line-height: 16px; 
                    font-weight: 400;
                }
            }
            .merchant-logo {
                border-radius: 8px; 
                width: 100px; 
                height: 100px;
                @media screen and (max-width: 600px) {
                    width: 57px; 
                    height: 57px;
                }
            }
            .background-image {
                object-fit: contain; 
                max-width: 100%;
                height: 100%;
                width: 100%;
                @media screen and (max-width: 600px) {
                    object-fit: revert;
                }   
            }
            .info-section {
                background: rgba(255, 255, 255, 0.98); 
                border: 4px solid #DCFF01; 
                margin-top: 40px; 
                border-radius: 31px; 
                padding: 20px 89px 81px 91px;
                @media screen and (max-width: 600px) {
                    margin-top: 15px; 
                    padding: 22px 20px 40px 20px;
                } 
            }
            .sub-heading {
                color: #081265; 
                text-align: center; 
                font-weight: 600; 
                font-family:  Arial, -apple-system, BlinkMacSystemFont, sans-serif; 
                font-size: 28px;
                @media screen and (max-width: 600px) {
                    font-size: 13px;
                    font-weight: 400;
                } 
            }
            .amount {
                color: #FF7878; 
                font-size: 57px; 
                font-weight: 800; 
                font-family: Arial; 
                margin: 30px 0;
                font-style: normal;
                @media screen and (max-width: 600px) {
                    font-size: 30px;
                    margin: 10px 0;
                } 
            }
            .info-detail {
                color: #081265; 
                text-align: center; 
                line-height: 41px; 
                font-weight: 600; 
                font-family: Arial, -apple-system, BlinkMacSystemFont, Arial, sans-serif; 
                font-size: 28px;
                @media screen and (max-width: 600px) {
                    font-size: 13px;
                    margin: 10px 0;
                    line-height: 16px;
                    font-weight: 400; 
                } 
            }
            .btn-cta {
                text-align: center; 
                color: #FDFDFD; 
                line-height: 60px; 
                text-decoration:none; 
                font-size: 24px;
                @media screen and (max-width: 600px) {
                    font-size: 13px;
                    margin: 10px 0;
                    line-height: initial; 
                } 
            }
            .link-container {
                margin: 20px 0 0 20px;
                @media screen and (max-width: 600px) {
                    margin: 21px 0 18px 0;
                } 
            }
            .link-cta {
                color: #8AAEFF; 
                font-size: 33px;
                font-weight: 600;
                font-family: Arial;
                @media screen and (max-width: 600px) {
                    font-size: 13px;
                } 
            }
            .footer-cta {
                margin: 40px 0 0 0; 
                font-size: 24px; 
                font-family: Arial; 
                font-weight: 400;
                color: #081265;
                @media screen and (max-width: 600px) {
                    font-size: 13px;
                    margin: 0; 
                } 
            }
            .mobile {
                display: block;
                @media screen and (min-width: 600px) {
                    display: none;
                }
            }
            .desktop {
                display: none;
                @media screen and (min-width: 600px) {
                    display: block;
                }
            }
        </style>
    </head>
    <body id='body'> 
        <div>
            <img class="background-image desktop" src="https://cdn.razorpay.com/static/assets/mopl_emailer.png" alt="missed_order_pl_desktop"  />
            <img class="background-image mobile" src="https://cdn.razorpay.com/static/assets/mopl-emailer-mobile.png" alt="missed_order_pl_mobile"  /> 
            <div class="layout">
                <center>
                    @if ($merchant['image'])
                        <div class="merchant-logo">
                            <img src="https://static.vecteezy.com/system/resources/previews/019/017/460/original/microsoft-transparent-logo-free-png.png" height="100%" width="100%" style="object-fit: contain;" />
                        </div>
                    @endif
                    <div class="info-section">
                        <div class="heading">Hi there 👋</div>
                        <div class="sub-heading">Looks like your payment has failed for amount</div>
                        <div class="amount" >
                            ₹ {{ $invoice['amount'] }}
                        </div>
                        <div class="info-detail">Come back and complete your payment now,<br /> it’s just a click away ⬇️</div>
                        <button class="mobile-btn"><a href="{{ $ctaHref }}" class="btn-cta">Retry Payment</a></button>
                        <div class="link-container"><a href="{{ $ctaHref }}" class="link-cta">Link</a></div>
                        <div class="footer-cta">Please note this link will expire in 24 hrs by {{ $invoice['expire_by_formatted'] }}</div>
                    </div>
                </center>
            </div>
        </div>
    </body>
</html>