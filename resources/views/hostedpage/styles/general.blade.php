<?php
    $rzp_prime_color    = '#528ff0';
    $canvas_bg          = '#f2f4f6';
    $primary_color      = '#0D2366';
    $secondary_color    = '#6F7691';
    $tertiary_color     = '#8894BA';
    $light_color        = '#fff';
    $bg_color_3         = '#FCFCFC;';
?>

<style>

    @import url('https://fonts.googleapis.com/css?family=Muli:400,600,700');

    * {
        box-sizing: border-box;
        font-family: inherit;
    }

    html {
        overflow: hidden;
    }

    html, body {
        height: 100%;
    }

    body {
        margin: 0;
        font-family: Muli,BlinkMacSystemFont,-apple-system,"Segoe UI","Roboto","Oxygen","Ubuntu","Cantarell","Fira Sans","Droid Sans","Helvetica Neue","Helvetica","Arial",sans-serif;
        color: {{$primary_color}};
        background: {{$canvas_bg}};
        overflow: auto;
    }

    body div.redirect-message {
        display: none;
    }

    body.has-redirect div.redirect-message {
        display: block;
    }

    b {
        font-weight: 600;
    }

    #hostedpage-container {
        position: relative;
    }

    #header-section {
        padding: 24px 0;
        overflow: auto;
        color: {{$light_color}};
    }

    .no-display-image #header-section {
        background: {{$data['merchant']['brand_color']}};
    }

    #header-details {
        float: left;
        display: table;
    }

    #header-details > div {
        vertical-align: middle;
    }

    #header-details #merchant-name {
        font-size: 24px;
        padding-left: 16px;
        display: table-cell;
    }

    #header-logo {
        text-align: center;
        position: relative;
        width: 64px;
        height: 64px;
        border-radius: 3px;
        line-height: 62px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        background: {{$light_color}};
        padding: 8px;
        display: inline-block;
    }

    #contact-details {
        font-size: 14px;
        line-height: 32px;
    }

    #contact-details svg {
        vertical-align: middle;
        height: 16px;
    }

    #description-section {
        background: {{$light_color}};
        color: {{$secondary_color}};
        position: relative;
    }

    #form-section {
        background: {{$bg_color_3}};
        position: relative;
    }

    .heading {
        color: {{$primary_color}};
        overflow-wrap: break-word;
        font-size: 16px;
        font-weight: bold;
        line-height: 24px;
    }

    a {
        text-decoration: none;
        color: {{$rzp_prime_color}};
        display: inline-block;
    }

    .footer a, #footer-section a, #contact-details a {
        color: inherit;
    }

    ol, p {
        padding: 0;
        font-size: 14px;
        line-height: 20px;
    }

    ol {
        padding-left: 15px;
        margin: 16px 0 32px 0;
    }

    ol li {
        margin-bottom: 10px;
    }

    p {
        white-space: pre-wrap;
        word-wrap: break-word;
        margin-top: 20px;
    }

    .btn {
        background-color: {{$data['merchant']['brand_color']}};
        color: #fff;
        border: 0;
        outline: none;
        cursor: pointer;
        font-size: 14px;
        padding: 10px 16px;
        border-radius: 2px;
        overflow: auto;
        position: relative;
    }

    .btn::after {
        content: '';
        position: absolute;
        width: 100%; height:100%;
        top:0; left:0;
        background:rgba(0,0,0,0.1);
        opacity: 0;
    }

    .btn:focus::after {
        opacity: 1;
    }

    .btn-link {
        color: {{$rzp_prime_color}};
        background: #fff;
        border: 0;
        cursor: pointer;
        padding: 0;
        font-size: 14px;
        outline: none;
        padding-left: 4px;
    }

    .text-underline {
        display: block;
        width: 24px;
        border-bottom: 4px solid {{$data['merchant']['brand_color']}};
        margin-top: 12px;
    }

    #footer-section {
        color: {{$tertiary_color}};
        font-size: 12px;
        border-radius: 4px;
    }

    #secure-lock-icon, #footer-section span {
        vertical-align: bottom;
    }

    #secure-lock-icon {
        height: 16px;
    }

    .btn:active {
        box-shadow: 0 0 0 1px rgba(0,0,0,.15) inset, 0 0 6px rgba(0,0,0,.2) inset;
    }

    .btn span {
        float: right;
        font-size: 15px;
    }

    .btn svg {
        fill: {{$light_color}};
    }

    #testmode-warning {
        padding: 12px 24px;
        background-color: #fcf8e3;
        color: #8a6d3b;
        font-size: 12px;
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
        position: relative;
    }

    #success-section {
        text-align: center;
        font-size: 14px;
        color: {{$secondary_color}};
        line-height: 20px;
        position: relative;
        height: 100%;

        display: none;
    }

    #success-section .heading {
        margin: 20px 0;
    }

    #success-section #success-footer{
        position: absolute;
        width: 100%;
        bottom: 0;
        left: 0;
    }

    @supports not (-ms-high-contrast: none) {
        /* Non-IE styles here */
        #success-section {
            overflow: hidden;
        }
    }

</style>
