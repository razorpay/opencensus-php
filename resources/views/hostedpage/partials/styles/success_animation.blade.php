<style>
    .animoo {
        position: relative;
        margin: 20% auto 0;
    }

    .animoo .circle, .animoo .checkmark {
        position: absolute;
        top: 50%;
        left: 50%;
    }

    .animoo .checkmark {
        width: 2.8em;
        transform: translate(-50%, -50%);
    }

    .animoo .checkmark::after {
        content: '';
        animation: checkmark 0.45s forwards ease;
        transform: scaleX(-1) rotate(135deg);
        opacity: 1;
        transform-origin: left top;
        border-right: 4px solid #5cb85c;
        border-top: 4px solid #5cb85c;
        position: absolute;
        left: 0;
    }


    .animoo .circle {
        border-radius: 50%;
        opacity: 0.03;
        background-color: #00BB55;
        transform: translate(-50%, -50%) scale(0);
        transform-origin: center;

        animation: appear 0.4s forwards cubic-bezier(0.3, 1, 1, 1);
    }

    .animoo, .circle.circle-1 {
        height: 164px;
        width: 164px;
    }

    .circle.circle-2 {
        height: 134px;
        width: 134px;
        animation-delay: 0.15s;
        animation-duration: 0.5s;
    }

    .circle.circle-3 {
        height: 104px;
        width: 104px;
        animation-delay: 0.3s;
        animation-duration: 0.6s;
    }


    @keyframes appear {
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
        }
        100% {
            height: 2.3em;
            width: 1em;
        }
    }

</style>