<?php
    $secondary_color = '#6F7691';
?>

<style>
    .form-group {
        margin-bottom: 28px;
    }

    .form-group label {
        display: inline-block;
        width: 140px;
        font-size: 14px;
        margin-right: 20px;
        line-height: 20px;
        text-align: right;
        color: {{$secondary_color}};
    }

    .form-group label, .form-group .form-control {
        vertical-align: middle;
    }

    .form-group .form-control {
        height: 36px;
        width: 306px;
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: 2px;
        background-color: #FFFFFF;
        outline: none;
        font-size: 14px;
        padding: 0 12px;
        line-height: 18px;
    }

    .form-group input:focus {
        border: 1px solid #528FF0;
    }

    .form-group select::before {
        /*content: '&#9660;';*/
        /*position: absolute;*/
        /*left: 236px;*/
        /*top: 4px;*/
        /*font-size: 20px;*/
        /*pointer-events: none;*/
    }

    .form-group select {
        /*-webkit-appearance: none;*/
        /*-webkit-border-radius: 2px;*/
    }

    @media (max-width: 924px) {
        .form-group label {
            width: 100%;
            margin-bottom: 4px;
            text-align: left;
        }

        .form-group .form-control {
            width: 100%;
        }
    }
    
</style>