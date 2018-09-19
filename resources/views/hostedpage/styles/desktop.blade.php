<?php
    $tertiary_color     = '#8894BA';
    $light_color        = '#fff';
    $border_color       = 'rgba(0,0,0,0.08)';
    $bg_color_1         = 'rgba(236, 241, 247, 0.28)';
    $footer_color1      = 'rgba(0,0,0,0.02)';
?>

<style>
    #desktop-container {
        max-width: 946px;
        min-width: 886px;

        padding: 0 16px;
        margin: 0 auto;
        display: none;
    }

    #desktop-container .content {
        border: 1px solid {{$border_color}};
        background: {{$light_color}};
        min-height: 540px;
    }

    #desktop-container.no-display-image::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 168px;
        top: 0;
        left: 0;
        background: {{$data['merchant']['brand_color']}};
        z-index: -1;
    }

    #desktop-container #header-details {
        max-width: 65%;
   }

    #desktop-container .content {
        display: table;
        border-collapse: collapse;
        border: 1px solid rgba(0,0,0,0.08);
        box-shadow: 4px 4px 8px 0 rgba(23,31,37,0.06);
    }

    #desktop-container #description-section, #desktop-container #form-section {
        display: table-cell;
        vertical-align: top;
        padding: 32px;
    }

    #desktop-container #form-section {
        padding-bottom: 93px;
    }

    #desktop-container #description-section {
        width: 40%;
        padding-bottom: 74px;
        border-right: 1px solid {{$border_color}};
    }

    .footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
    }

    .description-footer {
        padding: 16px 32px;
        border-bottom-left-radius: 2px;
        background-color: {{$bg_color_1}};
        font-size: 12px;
    }

    .form-footer {
        padding: 24px 32px;
        background: {{$footer_color1}};
        border-bottom-right-radius: 2px;
        overflow: auto;
    }

    .form-footer .btn {
        width: 205px;
        float: right;
    }

    .description-footer a {
        width: 100%;
    }

    .description-footer img {
        height: 18px;
        vertical-align: bottom;
        margin-left: 4px;
    }

    #desktop-container #testmode-warning {
        margin: 0 -32px;
        top: -32px;
    }

    /* Already setting innerHTML = null. This is for elements written in partials used by both mobile-desktop layouts */
    #desktop-container #mobile-proceed-btn,
    #desktop-container #form-section #footer-section,
    #desktop-container .mobile-submit-btn,
    #desktop-container .mobile-el,
    #desktop-container #rzp-logo {
        display: none;
    }

    #desktop-container.no-display-image #footer-section {
        margin: 40px auto 100px;
    }

    #desktop-container.no-display-image #footer-section #secure-lock-icon {
        display: inline-block;
        fill: {{$tertiary_color}};
    }

    #desktop-container #footer-section img {
        height: 20px;
    }

    #desktop-container #contact-details {
        float: right;
        text-align: right;
    }

    #desktop-container #contact-details svg {
        fill: {{$light_color}};
    }

    #desktop-container #fin-logo {
        float: right;
    }

    #desktop-container .form-footer .form-group {
        display: inline-block;
        margin-bottom: 0;
    }

    #desktop-container .form-footer .form-control {
        max-width: 260px;
        height: 40px;
    }

    #desktop-container .form-group .icon {
        line-height: 40px;
    }

    #desktop-container #success-section {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
    }

    #desktop-container .animoo {
        margin: 24% auto 0;
    }

    #desktop-container #success-section #success-footer {
        bottom: 16px;
    }

</style>
