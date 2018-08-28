<?php
    $light_color        = '#fff';
    $merchant_display_image = 'https://cdn.razorpay.com/static/assets/schindler-display-image.jpg';
?>

<style>
    .merchant-display-image {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background: url({{$merchant_display_image}}) no-repeat;
        z-index: -1;
        background-size: cover;
    }

    /* Desktop specific styles */
    #desktop-container .merchant-display-image {
        position: fixed;
        background-size: cover;
    }

    #desktop-container #footer-section {
        padding: 40px 0 100px;
        color: {{$light_color}};
    }

    #desktop-container  #footer-section #secure-lock-icon {
        display: inline-block;
        fill: {{$light_color}};
    }


    /* Mobile specific styles */
    #mobile-container .merchant-display-image {
        background-size: contain;
        background-position-y: -3%;
    }

</style>