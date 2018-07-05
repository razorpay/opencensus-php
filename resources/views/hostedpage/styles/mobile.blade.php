<?php
    $primary_text       = '#528FF0';
    $border_color       = 'rgba(0,0,0,0.08)';
    $secondary_color    = '#6F7691';
?>

<style>
    #mobile-container {
        min-height: 100vh;
        width: 100%;
        max-width: 412px;
        margin: 0 auto;

        display: none;
    }

    #mobile-container #header-section {
        padding-left: 24px;
        padding-right: 24px;
    }

    #mobile-container .content {
        height: 100vh;
        position: relative;
        z-index: 1;
    }

    #mobile-container #description-section, #mobile-container #form-section {
        padding: 32px 24px;
        overflow: auto;
    }

    #mobile-container #description-section {
        bottom: 0;
        top: 112px;
        position: absolute;
    }

    #mobile-container .back-btn {
        font-size: 12px;
        color: {{$primary_text}};
        background: transparent;
        outline: none;
        border: none;
        position: absolute;
        right: 0;
        top: 2px;
        padding: 0;
        line-height: 24px;
    }

    @media (max-width: 360px) {
        #mobile-container .back-btn {
            top: -20px;
        }
    }

    #mobile-container #form-section {
        height: 100vh;
        z-index: 2;
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
        background-image: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.1) 100%);
    }

    .btn--full {
        width: 100%;
        height: 56px;
    }

    #mobile-container #description-section {
        padding-bottom: 74px;
        border-right: 1px solid {{$border_color}};
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
    }

    #mobile-container #success-section {
        margin-bottom: 60px;
    }

</style>
