<style>
    .animoo {
        position: relative;
        margin: 20% auto 0;
    }

    .animoo .circle, .animoo .mark {
        position: absolute;
        top: 50%;
        left: 50%;
    }

    .animoo .mark {
        width: 2.8em;
        transform: translate(-50%, -50%);
    }

    .animoo .cross {
        font-weight: 600;
        font-size: 72px;
        color: #e60b34;
        margin: -4px 0 0 1px;
    }

    .animoo .check::after {
        content: '';
        animation: checkmark 0.4s linear forwards 0.45s;
        transform: scaleX(-1) rotate(135deg);
        opacity: 0;
        transform-origin: left top;
        border-right: 4px solid #5cb85c;
        border-top: 4px solid #5cb85c;
        position: absolute;
        left: 0;
        display: block;
    }


    .animoo .circle {
        border-radius: 50%;
        opacity: 0.06;
        background-color: #00BB55;
        transform: translate(-50%, -50%) scale(0);
        transform-origin: center;

        animation: appear 0.4s forwards cubic-bezier(0.54, 1.29, 0.8, 1.18);
    }

    .animoo.error .circle {
        background-color: #ff002f;
    }

    // Vertical position fix for cicles in IE
    .animoo .circle{
        display:flex;
        align-items:center;
    }
    .animoo .circle::after{
        content:'';
        min-height:inherit;
        font-size:0;
    }

    .animoo, .circle.circle-1 {
        height: 164px;
        width: 164px;
    }

    .circle.circle-2 {
        height: 134px;
        width: 134px;
        animation-delay: 0.15s;
        animation-duration: 0.6s;
    }

    .circle.circle-3 {
        height: 104px;
        width: 104px;
        animation-delay: 0.35s;
        animation-duration: 0.5s;
    }

    @supports not (-ms-high-contrast: none) {
       /* Non-IE styles here */

       .circle.spring {
           animation: appear 0.4s forwards cubic-bezier(0.54, 1.29, 0.8, 1.18), appear-spring 2s forwards 0.85s infinite;
       }
    }


    @media screen and (min-width:0\0) and (min-resolution: .001dpcm) {
        /* IE-9 only styles */

        .animoo .circle, .animoo .mark {
            left: 0;
            top: 0;
        }

        .animoo .mark {
            margin-top: 58px;
            margin-left: 72px;
        }

        .animoo .mark::after {
            height: 2.3em;
            width: 1em;
            opacity: 1;
            -ms-transform: scaleX(-1) rotate(135deg);
        }

        .animoo .circle-2 {
            margin-top: 15px;
            margin-left: 15px;
        }

        .animoo .circle-3 {
            margin-top: 30px;
            margin-left: 30px;
        }
    }

    @keyframes appear {
        0% {
            transform: translate(-50%, -50%) scale(0);
        }
        100% {
            transform: translate(-50%, -50%) scale(1);
        }
    }

    @-moz-keyframes appear {
        0% {
            transform: translate(-50%, -50%) scale(0);
        }
        100% {
            transform: translate(-50%, -50%) scale(1);
        }
    }

    @-webkit-keyframes appear {
        0% {
            transform: translate(-50%, -50%) scale(0);
        }
        100% {
            transform: translate(-50%, -50%) scale(1);
        }
    }

    @keyframes checkmark {
        0% {
            height: 0;
            width: 0;
        }
        50% {
            height: 0;
            width: 1em;
            opacity: 1;
        }
        100% {
            height: 2.3em;
            width: 1em;
            opacity: 1;
        }
    }

    @-moz-keyframes checkmark {
        0% {
            height: 0;
            width: 0;
        }
        50% {
            height: 0;
            width: 1em;
            opacity: 1;
        }
        100% {
            height: 2.3em;
            width: 1em;
            opacity: 1;
        }
    }

    @-webkit-keyframes checkmark {
        0% {
            height: 0;
            width: 0;
        }
        50% {
            height: 0;
            width: 1em;
            opacity: 1;
        }
        100% {
            height: 2.3em;
            width: 1em;
            opacity: 1;
        }
    }

    @keyframes appear-spring {
        0% {
            transform: translate(-50%, -50%) scale(1);
        }

        50% {
            transform: translate(-50%, -50%) scale(1.05);
        }

        100% {
            transform: translate(-50%, -50%) scale(1);
        }
    }

    @-moz-keyframes appear-spring {
        0% {
            transform: translate(-50%, -50%) scale(1);
        }

        50% {
            transform: translate(-50%, -50%) scale(1.05);
        }

        100% {
            transform: translate(-50%, -50%) scale(1);
        }
    }

    @-webkit-keyframes appear-spring {
        0% {
            transform: translate(-50%, -50%) scale(1);
        }

        50% {
            transform: translate(-50%, -50%) scale(1.05);
        }

        100% {
            transform: translate(-50%, -50%) scale(1);
        }
    }

</style>
