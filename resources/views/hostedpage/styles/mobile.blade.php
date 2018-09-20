<?php
    $primary_text       = '#528FF0';
    $border_color       = 'rgba(0,0,0,0.08)';
    $secondary_color    = '#6F7691';
?>

<style>
    #mobile-container {
        min-height: 80vh;
        width: 100%;
        max-width: 412px;
        margin: 0 auto;

        display: none;
    }

    #mobile-container #header-section {
        top: 0;
        padding-left: 24px;
        padding-right: 24px;
        position: absolute;
        z-index: 1;
        width: 100%;
    }

    #mobile-container .content {
        height: 80vh;
        position: relative;
        z-index: 1;
        padding: 112px 0 56px;
    }

    #mobile-container .back-btn {
        fill: {{$secondary_color}};
        background: transparent;
        outline: none;
        border: none;
        margin-left: -16px;
        padding: 0 16px;
        vertical-align: middle;
        display: inline-block;
        line-height: 12px;
    }

    #mobile-container #form-section {
        height: 80vh;
        z-index: 2;
        padding: 32px 24px;
        overflow: auto;
    }

    #mobile-container #form-section {
        transition: 0.3s;
        transform: translateY(0);
    }

    #mobile-container .form-group .help-block {
        margin-left: 0;
    }

    .slideup {
        transform: translateY(-100%) !important;
    }

    #mobile-proceed-btn {
        position: absolute;
        bottom: 0;
        z-index: 100;
        text-align: center;
        line-height: 36px;
        background-image: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.1) 100%);
    }

    .btn--full {
        width: 100%;
        height: 56px;
    }

    #mobile-container #description-section {
        border-right: 1px solid {{$border_color}};
        position: relative;
        height: 100%;
        padding: 16px 32px;
        overflow: auto;
    }

    #mobile-container form {
        position: relative;
    }

    #mobile-container #udf_submit_btn {
        background-image: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.1) 100%);
    }

    /* Already setting innerHTML = null. This is for elements written in partials used by both mobile-desktop layouts */
    #mobile-container .footer,
    #mobile-container #footer-section #secure-lock-icon {
        display: none;
    }

    #mobile-container #footer-section {
        border: 1px solid #dfdfdf;
        padding: 16px;
        margin: 56px auto 28px;
    }

    #mobile-container #footer-section img {
        height: 18px;
    }

    #mobile-container #fin-logo {
        margin-top: 16px;
        display: block
    }

    #mobile-container #rzp-logo {
        vertical-align: bottom;
        margin-left: 4px;
    }

    #mobile-container #contact-details {
        padding: 0 16px;
    }

    #mobile-container #contact-details svg {
        fill: {{$secondary_color}};
    }

    #mobile-container #testmode-warning {
        margin: 0 -24px;
        top: -32px;
    }

    #mobile-container .form-group label {
        width: 100%;
        margin-bottom: 4px;
        text-align: left;
    }

    #mobile-container .form-group .form-control {
        width: 100%;
        height: 40px;
    }

    #mobile-container #success-section {
        margin-bottom: 60px;
    }

</style>
