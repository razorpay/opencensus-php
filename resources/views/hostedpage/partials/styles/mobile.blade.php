<?php
    $border_color = 'rgba(0,0,0,0.08)';
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

    #mobile-container #form-section {
        height: 100vh;
        z-index: 2;
    }

    #mobile-container #form-section {
        transition: 0.3s;
        transform: translateY(0);
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

    #mobile-container .footer,
    #mobile-container #footer-section #secure-lock-icon,
    #mobile-container #contact-details {
        display: none;
    }

    #mobile-container #footer-section {
        border: 1px solid #dfdfdf;
        padding: 8px 16px;
        margin: 28px auto;
    }

    #mobile-container #footer-section img {
        margin: 12px 0;
        display: block
    }

    #mobile-container #testmode-warning {
        margin: 0 -24px;
        top: -32px;
    }

</style>