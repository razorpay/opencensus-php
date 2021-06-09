<style>
    * {
        box-sizing: border-box;
    }
    body {
        margin: 0;
        font-family: "Lato",ubuntu,helvetica,sans-serif;
        color: #414141;
        background: #fff;
    }

    #success path {
        fill: #6DCA00;
    }

    #failure path {
        fill: #e74c3c;
    }

    .card {
        background: #fff;
        border-radius: 2px;
        box-shadow: 0 2px 9px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin: 30px auto;
        width: 80%;
        max-width: 300px;
        text-align: center;
    }

    #success {
        display: none;
    }

    .paid #success {
        display: block;
    }

    .issued #partial {
        display: none;
    }

    #button {
        background-color: #4994E6;
        color: #fff;
        border: 0;
        outline: none;
        cursor: pointer;
        font: inherit;
        margin-top: 10px;
        padding: 10px 20px;
        border-radius: 2px;
    }

    #button:active {
        box-shadow: 0 0 0 1px rgba(0,0,0,.15) inset, 0 0 6px rgba(0,0,0,.2) inset;
    }

    body div.redirect-message {

        display: none;
    }

    body.has-redirect div.redirect-message {

        display: block;
    }

    #desktop-container {
        width: 100%;
        min-width: 845px;
        display: none;
    }

    #desktop-container > div {
    //position: absolute;
    //transform: translate(-50%, -50%);
        left: 50%;
        top: 50%;
    }

    #mobile-container {
        position: relative;
        display: none;
        background: #eaeaea;
    }

    #payment-container {
        width: 100%;
        position: relative;
        max-width: 880px;
        margin: 40px auto 0;
    }

    .table-box {
        display: inline-block;
        vertical-align: middle;
    }

    .table-box > div {
        min-width: 350px;
    }

    #inv-info-par {
        max-width: 600px;
        width: 60%;
    }

    #chkout-par {
        width: 39%;
        max-width: 350px;
    }

    #chkout-box {
        width: 100%;
        margin: 0 auto;
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
        min-height: 511px;
        background-color: #fff;
        overflow: hidden;
        position: relative;
        z-index: 0;
        margin-left: -15px;
    }

    .short#chkout-box {
        min-height: 460px;
    }

    #overlay {
        position: fixed;
        width: 100%;
        height: 100%;
        left: 0;
        top: 0;
        background-color: rgba(0, 0, 0, 0.05);
        opacity: 0;
        z-index: 0;
        pointer-events: none;
        transition: 0.5s all ease-in-out;
    }

    #overlay.overlay-hist {
        pointer-events: all;
        background-color: rgba(0, 0, 0, 0.4);
        transition: 0.24s all ease-in-out;
        z-index: 1;
    }

    #payment-container iframe.razorpay-checkout-frame {
        min-height: 511px !important;
    }

    #inv-info-box {
        max-width: 600px;
        width: 100%;
        margin: 0 auto;
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
        border: 1px solid #dfdfdf;
        border-radius: 4px;
        min-height: 270px;
    }

    .inv-details {
        padding: 30px 40px;
        background-color: #fff;
        border-radius: 4px;
    }

    .inv-details .inv-for {
        font-size: 20px;
        font-weight: 600;
    }

    .inv-details {
        font-size: 14px;
    }

    .inv-details .info {
        color: #a5a5a5;
        line-height: 22px;
        font-size: 13px;
    }

    #desktop-container .inv-details .info {
        margin-top: 16px;
    }

    #mobile-container .inv-details .info {
        margin-top: 8px;
    }

    #mobile-container .inv-details .info {
        margin-bottom: 16px;
    }

    .inv-details .info .val {
        color: #414141;
        font-size: 14px;
    }

    .info .light {
        color: #969a9a;
    }

    #partial-payment-info {
        display: none;
    }

    .inv-details .info #display-pay-amt {
        font-weight: 600;
        font-size: 20px;
    }

    .inv-details .info #display-pay-amt > span {
        position: relative;
    }

    #paid-tag {
        padding: 0 4px;
        border-radius: 4px;
        font-size: 18px;
        border: 3px solid #ff5353;
        color: #ff5353;
        position: absolute;
        transform: rotate(-16deg) scaleY(1.15);
        right: -48px;
        top: -8px;
    }

    .line-strike {
        display: block;
        width: 18px;
        border-bottom: 2px solid #18bd5a;
        margin-top: 12px;
    }

    #inv-info-box .footer {
        padding: 20px 40px;
        border-bottom-right-radius: 4px;
        border-bottom-left-radius: 4px;
        color: #717171;
        font-size: 12px;
        line-height: 1.5;
    }

    #inv-info-box .footer div,
    #inv-info-box .footer span {
        display: inline-block;
    }

    #inv-info-box .footer span {
        margin-right: 16px;
    }

    #inv-info-box .footer img {
        vertical-align: middle;
        margin-right: 3px;
    }

    #cancelled-crack {
        width: 100%;
        margin-top: 114px;
        background-image: url(https://cdn.razorpay.com/static/cancelled_invoice.png);
        background-repeat: no-repeat;
        background-position: -189px -90px;
        height: 80px;
        display: none;
    }

    #mobile-container #cancelled-invoice {
        width: 100%;
        top: -12px;
        background-image: url(https://cdn.razorpay.com/static/cancelled_invoice.png);
        background-repeat: no-repeat;
        background-position: -19px -147px;
        font-size: 20px;
        padding: 40px;
        line-height: 20px;
        min-height: 225px;
        display: none;
    }

    #cancelled-invoice {
        text-align: center;
        line-height: 20px;
    }

    #mobile-container #cancelled-invoice {
        padding: 45px 24px;
    }

    #desktop-container #cancelled-invoice {
        padding: 30px;
    }

    #cancelled-invoice .title {
        font-weight: 600;
        margin-top: 16px;
    }

    #cancelled-invoice .desc {
        font-size: 14px;
        color: #777777;
        margin-top: 16px;
    }

    #footer {
        margin: 40px auto 28px;
        max-width: 655px;
        width: 85%;
        padding: 15px 24px;
        background-color: #fcfcfc;
        border: 1px solid #dfdfdf;
        font-size: 12px;
        border-radius: 4px;
        box-shadow: 0 0 15px rgba(0,0,0,0.08);
        color: #787878;
        overflow: auto;
    }

    #footer a {
        color: #8a8a8a;
    }

    #footer img {
        height: 24px;
        margin-bottom: 4px;
    }

    #footer .report-cta {
        margin-top: 15px;
    }

    #footer .report-cta a {
        color: #528FF0;
        text-decoration: none;
    }

    #footer .report-cta img {
        vertical-align: middle;
        height: 13px;
        margin: 0 3px;
    }


    .bg-svg {
        position: absolute;
        z-index: -100;
        top: -35px;
        width: 100%;
    }

    #payment-container--mob {
        width: 100%;
        max-width: 412px;
        padding-bottom: 80px;
        margin: 0 auto;
        border: 1px solid #dfdfdf;
        box-shadow: 0 0 10px rgba(0,0,0,0.08);
        min-height: 100vh;
    }

    #mobile-container .inv-details{
        background-color: #fff;
        border-radius: 4px;
        padding: 22px 24px;
    }

    #payment-container--mob #inv-info-container {
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
        border-radius: 4px;
        margin: 12px;
        border: 1px solid #dfdfdf;
        background: #f5f5f5;
    }



    #chkout-header {
        padding: 24px;
        overflow: hidden;
        max-height: 128px;
        position: relative;
        color: #fff;
        background: #fff;
    }

    #mob-payment-btn {
        position: fixed;
        bottom: 0;
        width: 100%;
        max-width: 411px;
        background: #fff;
        z-index: 100;
        height: 55px;
        font-size: 16px;
        color: #fff;
        border: 0;
        background-image: linear-gradient(to bottom right,rgba(255,255,255,0.2),rgba(0,0,0,0.2));
        cursor: pointer;
        display: none;
    }

    #desk-payment-btn {
        position: relative;
        top: 280px;
        width: 196px;
        background: #fff;
        margin: 0 auto;
        z-index: 100;
        height: 55px;
        font-size: 16px;
        color: #fff;
        border: 0;
        background-image: linear-gradient(to bottom right,rgba(255,255,255,0.2),rgba(0,0,0,0.2));
        cursor: pointer;
        display: none;
    }

    #chkout-header:before {
        content: "";
        left: 0;
        right: 0;
        bottom: 0;
        top: 0;
        position: absolute;
        background-image: linear-gradient(to bottom right,rgba(255,255,255,0.2),rgba(0,0,0,0.2));
    }

    #desktop-container #chkout-header {
        position: absolute;
        top: 0;
        width: 100%;
    }

    #header-logo {
        text-align: center;
        position: relative;
        height: 80px;
        border-radius: 3px;
        line-height: 62px;
        float: left;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    #header-logo.visible {
        background: #fff;
        padding: 8px;
        width: 80px;
        margin-right: 24px;
    }

    #header-details {
        white-space: nowrap;
        position: relative;
    }

    #header-details #merchant {
        margin-top: 18px;
    }

    #header-details #merchant-name {
        text-overflow: ellipsis;
        overflow: hidden;
        font-size: 20px;
    }

    #header-details #merchant-desc {
        white-space: pre;
        text-overflow: ellipsis;
        overflow: hidden;
        opacity: .8;
        font-size: 14px;
    }

    #header-details #amount {
        font-size: 24px;
        margin-top: 10px;
    }

    #payment-container--mob #footer {
        width: auto;
        margin: 12px;
        padding: 12px 18px
    }

    #payment-container--mob #fin-logo{
        padding: 5px 0;
        margin-top: 10px;
        margin-bottom: 0;
    }

    #payment-container--mob #footer .report-cta {
        margin-top: 5px;
    }

    #scs-box img {
        padding-left: 2px;
        width: 48px;
        margin: 4px auto;
    }


    #scs-box {
        line-height: 28px;
        background-color: #fff;
        padding: 36px 30px;
        text-align: center;
        font-size: 14px;
        top: 0;
        width: 100%;
        position: absolute;
        margin: 175px auto 0;
        display: none;
        background: #effff6;
    }

    #payment-for {
        position: relative;
    }
    .btn-link {
        color: #528ff0;
        background: linear-gradient(transparent, rgba(255,255,255,0.8));
        border: 0;
        cursor: pointer;
        padding: 0;
        font-size: 14px;
        outline: none;
    }

    .showmore {
        padding-left: 10px;
        margin-left: -9px;
    }

    #desktop-container .showhistory {
        margin-top: 16px;
    }

    #hist-modal {
        position: fixed;
        width: 92%;
        max-width: 460px;
        left: 50%;
        top: 48%;
        line-height: 24px;

        background: #fff;
        border-radius: 4px;
        box-shadow: 0 0 10px rgba(0,0,0,0.4);
        color: #909090;
        max-height: 70vh;
        overflow: scroll;
        transition: .1s all ease-in;
        transform: translate(-50%,-50%) scale(0.7);
        opacity: 0;
        z-index: 2;
        pointer-events: none;
    }

    #hist-modal.show {
        display: block;
        transform: translate(-50%,-50%) scale(1);
        opacity: 1;
        pointer-events: all;
    }

    #hist-close {
        position: absolute;
        right: 10px;
        top: 10px;
        padding: 10px;
        font-size: 18px;
        cursor: pointer;
        color: #57666e;
    }

    .modal-title {
        padding: 24px 20px;
        font-size: 18px;
        font-weight: 600;
        color: #2e3345;
    }
    .modal-desc {
        font-size: 14px;
        font-weight: 400;
        color: #909090;
    }

    .modal-col {
        padding: 24px 20px;
        border-top: 1px solid #e0e0e0;
    }

    .modal-col .row:nth-of-type(n+2) {
        font-size: 13px;
    }

    .testmode-warning {
        padding: 12px 24px;
        background-color: #fcf8e3;
        color: #8a6d3b;
        font-size: 12px;
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
        position: relative;
        display: block;
    }

    .testmode-warning + .inv-details {
        border-radius: 0 !important;
    }

    #desktop-container .testmode-warning {
        padding-left: 40px;
    }

    .external-link {
        text-decoration: underline;
        cursor: pointer;
    }
</style>
