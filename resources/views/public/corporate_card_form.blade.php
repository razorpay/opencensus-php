<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- <link rel="stylesheet" href="https://x.razorpay.com/dist/app.e6a5d2d3.css"> -->
    <style>
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track-piece {
            background-color: rgba(255, 255, 255, .05);
        }

        ::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, .2);
        }

        /* latin-ext */
        @font-face {
            font-family: 'Muli';
            font-style: normal;
            font-weight: 400;
            src: local('Muli Regular'), local('Muli-Regular'), url(https://fonts.gstatic.com/s/muli/v12/7Auwp_0qiz-afTzGLQjUwkQ1OQ.woff2) format('woff2'), url(/dist/assets/fonts/muli-v12-latin-ext-regular.woff) format('woff');
            unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }

        /* latin */
        @font-face {
            font-family: 'Muli';
            font-style: normal;
            font-weight: 400;
            src: local('Muli Regular'), local('Muli-Regular'), url(https://fonts.gstatic.com/s/muli/v12/7Auwp_0qiz-afTLGLQjUwkQ.woff2) format('woff2'), url(/dist/assets/fonts/muli-v12-latin-regular.woff) format('woff');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }

        /* latin-ext */
        @font-face {
            font-family: 'Muli';
            font-style: normal;
            font-weight: 600;
            src: local('Muli SemiBold'), local('Muli-SemiBold'), url(https://fonts.gstatic.com/s/muli/v12/7Au_p_0qiz-ade3iOCv2z24PMFk-0g.woff2) format('woff2'), url(/dist/assets/fonts/muli-v12-latin-ext-600.woff) format('woff');
            unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }

        /* latin */
        @font-face {
            font-family: 'Muli';
            font-style: normal;
            font-weight: 600;
            src: local('Muli SemiBold'), local('Muli-SemiBold'), url(https://fonts.gstatic.com/s/muli/v12/7Au_p_0qiz-ade3iOCX2z24PMFk.woff2) format('woff2'), url(/dist/assets/fonts/muli-v12-latin-600.woff) format('woff');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }

        /* latin-ext */
        @font-face {
            font-family: 'Muli';
            font-style: normal;
            font-weight: 700;
            src: local('Muli Bold'), local('Muli-Bold'), url(https://fonts.gstatic.com/s/muli/v12/7Au_p_0qiz-adYnjOCv2z24PMFk-0g.woff2) format('woff2'), url(/dist/assets/fonts/muli-v12-latin-ext-700.woff) format('woff');
            unicode-range: U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }

        /* latin */
        @font-face {
            font-family: 'Muli';
            font-style: normal;
            font-weight: 700;
            src: local('Muli Bold'), local('Muli-Bold'), url(https://fonts.gstatic.com/s/muli/v12/7Au_p_0qiz-adYnjOCX2z24PMFk.woff2) format('woff2'), url(/dist/assets/fonts/muli-v12-latin-700.woff) format('woff');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }

        html,
        body {
            font-family: Muli;
            font-size: 14px;
            line-height: 1.5em;
            margin: 0;
        }

        html {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        html,
        body,
        #boot-loader,
        #react-root {
            width: 100%;
            height: 100%;
            font-size: 16px;
        }

        #boot-loader {
            display: table;
            position: absolute;
            left: 0;
            top: 0;
            text-align: center;
            font-size: 2em;
            background-color: #171b2f;
        }

        #boot-loader>div {
            display: table-cell;
            vertical-align: middle;
        }

        input:-internal-autofill-previewed,
        input:-internal-autofill-selected,
        textarea:-internal-autofill-previewed,
        textarea:-internal-autofill-selected,
        select:-internal-autofill-previewed,
        select:-internal-autofill-selected {
            background-color: transparent !important;
            background-image: none !important;
            color: inherit !important;
        }

        .animated-logo {
            width: 150px;
            height: 85px;
            margin: 0 auto;
            overflow: hidden;
            position: relative;
        }

        .animated-logo>img {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            will-change: transform;
            transform: translate(0, 0);
            -webkit-animation: play 1.5s steps(60) infinite;
            animation: play 1.5s steps(60) infinite;
        }

        .x-tips {
            font-size: 1rem;
            margin-top: 3rem;
            color: rgba(255, 255, 255, 0.54);
        }

        .x-tips p {
            margin-top: .5rem;
            color: rgba(255, 255, 255, 0.87);
        }

        @-webkit-keyframes play {
            100% {
                transform: translate(-100%, 0);
            }
        }

        @keyframes play {
            100% {
                transform: translate(-100%, 0);
            }
        }
    </style>

</head>
<body>
<div id="app"></div>
<script src="/dist/addCard.js"></script>
</body>
</html>
