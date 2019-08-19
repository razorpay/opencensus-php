<?php
    $secondary_color    = '#6F7691';
?>


@include('hostedpage.styles.success_animation')
<style>
        @import url('https://fonts.googleapis.com/css?family=Muli:400,600,700');

        * {
            box-sizing: border-box;
            font-family: inherit;
        }

        body {
            margin: 0;
            font-family: Muli,BlinkMacSystemFont,-apple-system,"Segoe UI","Roboto","Oxygen","Ubuntu","Cantarell","Fira Sans","Droid Sans","Helvetica Neue","Helvetica","Arial",sans-serif;
            background: #fff;
        }

        .animoo {
            margin-top: 5%;
        }

        #success-section {
            margin-top: 64px;
        }

        #success-section, #post-msg {
            text-align: center;
            font-size: 14px;
            color: {{$secondary_color}};
            line-height: 20px;
            position: relative;
        }

        #post-msg {
            margin-top: -12px;
        }

        #post-msg a {
            padding: 12px 24px;
            text-decoration: none;
            background: #528ff0;
            border-radius: 2px;
            color: #fff;
            font-size: 14px;
            display: inline-block;
            margin-top: 48px;
        }

        #success-section .heading {
            margin: 20px 0;
        }

        #success-section #success-footer {
            margin-top: 12px;
        }

        @supports not (-ms-high-contrast: none) {
            /* Non-IE styles here */
            #success-section {
                overflow: hidden;
            }
        }
</style>
