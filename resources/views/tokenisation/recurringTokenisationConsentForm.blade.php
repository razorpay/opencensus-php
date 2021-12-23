<!DOCTYPE html>
<html lang="en">
<head>
    <title>Razorpay | Consent Page</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link
        href="https://fonts.googleapis.com/css?family=Muli:400,600,800"
        rel="stylesheet"
    />
    <style type="text/css">
        body,
        html {
            padding: 0;
            margin: 0;
        }
        body {
            background-image: url("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzYwIiBoZWlnaHQ9IjEwMCIgdmVyc2lvbj0iMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+PGRlZnM+PHBhdGggaWQ9ImEiIGQ9Ik0wIDBoMzYwdjEwMEgweiIvPjxwYXRoIGlkPSJjIiBkPSJNMCAwaDM3NHYxODFIMHoiLz48bGluZWFyR3JhZGllbnQgeDE9IjAlIiB5MT0iMTcwJSIgeDI9IjAlIiB5Mj0iMCUiIGlkPSJlIj48c3RvcCBzdG9wLWNvbG9yPSIjN0VERUZGIiBvZmZzZXQ9IjAlIi8+PHN0b3Agc3RvcC1jb2xvcj0iIzAwNDFCMSIgb2Zmc2V0PSIxMDAlIi8+PC9saW5lYXJHcmFkaWVudD48L2RlZnM+PGcgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj48bWFzayBpZD0iYiIgZmlsbD0iI2ZmZiI+PHVzZSB4bGluazpocmVmPSIjYSIvPjwvbWFzaz48ZyBtYXNrPSJ1cmwoI2IpIj48ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtOCAtNDMpIj48bWFzayBpZD0iZCIgZmlsbD0iI2ZmZiI+PHVzZSB4bGluazpocmVmPSIjYyIvPjwvbWFzaz48ZyBtYXNrPSJ1cmwoI2QpIj48cGF0aCBmaWxsPSIjRjRGOEZGIiBkPSJNLTQuNjExLTUuMzEyaDM4OS4zMjN2ODkuOTVMMjIuODkgMTMxLjE5N2wtMjcuNTAxLTE2LjIyNXoiLz48cGF0aCBmaWxsPSJ1cmwoI2UpIiBkPSJNMCAwaDM4OS4zMjR2MTI3Ljk3NGwtMjYuNjYyIDI0LjE3TDAgMTAxLjg4eiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTE1IC0xMCkiLz48L2c+PC9nPjwvZz48L2c+PC9zdmc+");
            background-repeat: no-repeat;
            background-size: contain;
            background-position-x: center;
            background-position-y: top;
            font-family: Muli, sans-serif;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }
        button.disabled {
            pointer-events: none;
            cursor: not-allowed;
        }
        .powered-by {
            font-weight: 400;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .powered-by span {
            font-weight: 600;
            font-size: 12px;
            line-height: 15px;
            color: #1f2849;
            mix-blend-mode: normal;
            opacity: 0.5;
        }
        .powered-by img {
            height: 18px;
            margin-left: 4px;
        }
        #app {
            margin: 0 auto;
            width: 95%;
            min-height: 100vh;
            max-width: 640px;
        }
        #app-container {
            margin: 24px 0;
            position: relative;
        }
        #view-content {
            /* width: 90%; */
            max-width: 640px;
            margin: 0 auto;
            padding: 0 24px;
            border-radius: 3px;
            background-color: #fff;
            -webkit-box-shadow: 0 8px 16px 0 rgba(0, 0, 0, 0.1);
            box-shadow: 0 8px 16px 0 rgba(0, 0, 0, 0.1);
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -webkit-flex-direction: column;
            -ms-flex-direction: column;
            flex-direction: column;
            -webkit-box-pack: justify;
            -webkit-justify-content: space-between;
            -ms-flex-pack: justify;
            justify-content: space-between;
        }
        .hide-sm {
            display: block;
        }
        .primary-button {
            border: 1px solid #3395ff;
            height: 45px;
            color: white;
            background: #3395ff;
            border-radius: 4px;
            padding: 14px 30px;
            cursor: pointer;
            font-weight: 600;
        }
        .secondary-button {
            border: 1px solid #a7a5a5;
            height: 45px;
            color: black;
            background: white;
            border-radius: 4px;
            padding: 14px 30px;
            cursor: pointer;
            opacity: 0.6;
        }
        .submit-button-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .title-card {
            padding: 17px 24px;
            background: #f4f8fe;
        }
        .title-text {
            font-weight: 600;
            font-size: 18px;
            line-height: 150%;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 12px;
        }
        .small-separator {
            background: #49dab5;
            height: 4px;
            width: 54px;
        }
        .divider {
            position: relative;
        }
        .divider:after {
            content: " ";
            border: 1px solid rgba(0, 0, 0, 0.059);
            width: calc(100% + 48px);
            margin-left: -24px;
            display: block;
            height: 0;
        }
        .powered-by-header {
            text-align: right;
        }
        .content {
            padding: 20px;
        }
        .content p {
            /* margin-top: 24px; */
            font-size: 16px;
            line-height: 21px;
            color: #404040;
            opacity: 0.7;
        }
        .content p span {
            display: inline-flex;
        }
        .content p span span:last-child {
            margin-left: 5px;
        }
        @media screen and (max-width: 760px) {
            .hide-sm {
                display: none !important;
            }
            .title-text {
                margin-bottom: 6px;
                font-size: 16px;
            }
            #view-content > div > div {
                padding: 13px;
            }
            .divider:after {
                width: calc(100% + 30px);
                margin-left: -15px;
            }
            .content {
                padding: 15px 0px;
            }
            #view-content,
            #timer-content {
                width: 90%;
                padding: 5px;
            }
            .content p {
                font-size: 13px;
                line-height: 18px;
            }
        }
        @media screen and (min-width: 761px) {
            .no-desktop {
                display: none;
            }
            button {
                margin: 0px 8px;
            }
            .primary-button {
                min-width: 200px;
            }
            .secondary-button {
                min-width: 150px;
            }
            #view-content > div > div {
                padding: 15px 0px;
            }
        }
        @media screen and (max-width: 460px) {
            #app {
                width: 100%;
            }
            #view-content > div > div:last-child {
                padding-bottom: 15px;
                margin-top: -20px;
            }
            button {
                margin: 0px 5px;
            }
            #timer-content {
                width: 100%;
                max-width: 460px;
                position: fixed;
                bottom: 0px;
                font-size: 12px;
            }
        }
    </style>

    <meta name="theme-color" content="#2A70C8" />
</head>

<body>
<div id="app">
    <div id="app-container">
        <div id="view-content">
            <div class="powered-by-header hide-sm">
                <div class="powered-by">
                    <span>Powered by</span>
                    <img src="https://cdn.razorpay.com/logo.svg" alt="Razorpay" />
                </div>
            </div>
            <span class="divider hide-sm"></span>
            <div>
                <div>
                    <div class="title-card">
                        <div>
                            <svg
                                width="71"
                                height="71"
                                viewBox="0 0 71 71"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <circle cx="35.5" cy="35.5" r="35.5" fill="#F4F8FF" />
                                <path
                                    d="M63.2615 29.1388C61.0448 32.078 57.9601 34.246 54.4438 35.3362C50.9275 36.4263 47.1574 36.3835 43.6668 35.2137C40.1762 34.0439 37.1416 31.8064 34.9922 28.8175C32.8429 25.8287 31.6875 22.2398 31.6895 18.5584C31.6895 17.987 31.7165 17.4221 31.7707 16.8635C32.0326 14.1633 32.9167 11.5606 34.3535 9.25941C35.7903 6.95825 37.7407 5.02131 40.0518 3.60055C42.3629 2.17979 44.9718 1.31386 47.6737 1.07069C50.3757 0.827514 53.0973 1.21372 55.6249 2.199C58.1525 3.18428 60.4175 4.74184 62.2421 6.74948C64.0666 8.75712 65.4012 11.1602 66.1411 13.7703C66.881 16.3803 67.006 19.1263 66.5064 21.7928C66.0068 24.4592 64.8961 26.9737 63.2615 29.1388ZM59.0899 20.2105V11.1362L49.3906 6.37257L49.3603 6.38771L39.6912 11.1224V20.2105C39.6912 22.361 41.1396 25.1076 42.9253 26.3247L49.3906 30.7345L55.8573 26.3247C57.6429 25.1076 59.0899 22.3748 59.0899 20.2105Z"
                                    fill="white"
                                />
                                <path
                                    d="M63.2612 37.1572V55.3526C63.2611 56.0944 63.115 56.8288 62.8311 57.5141C62.5472 58.1994 62.131 58.822 61.6065 59.3464C61.0819 59.8708 60.4592 60.2868 59.7739 60.5705C59.0885 60.8543 58.354 61.0003 57.6123 61.0001H5.64751C4.1497 61.0001 2.71323 60.4051 1.65412 59.346C0.595003 58.2869 0 56.8504 0 55.3526V37.1572H63.2612ZM26.6873 49.5839C26.6872 48.7601 26.4427 47.9548 25.9849 47.2699C25.5271 46.585 24.8765 46.0512 24.1153 45.736C23.3541 45.4209 22.5166 45.3385 21.7086 45.4994C20.9006 45.6602 20.1584 46.0571 19.576 46.6397C18.9936 47.2224 18.597 47.9646 18.4364 48.7727C18.2758 49.5807 18.3584 50.4182 18.6738 51.1793C18.9892 51.9404 19.5232 52.5908 20.2083 53.0484C20.8933 53.506 21.6987 53.7502 22.5226 53.75C23.0696 53.75 23.6112 53.6422 24.1166 53.4328C24.6219 53.2233 25.0811 52.9164 25.4678 52.5296C25.8545 52.1427 26.1613 51.6835 26.3705 51.1781C26.5798 50.6726 26.6874 50.131 26.6873 49.5839ZM16.8035 49.5839C16.8033 48.7602 16.5589 47.9549 16.1012 47.27C15.6434 46.5851 14.9929 46.0513 14.2318 45.7362C13.4707 45.421 12.6332 45.3385 11.8253 45.4993C11.0173 45.66 10.2752 46.0567 9.69266 46.6392C9.11016 47.2217 8.71345 47.9639 8.55271 48.7718C8.39196 49.5798 8.47439 50.4173 8.78958 51.1784C9.10476 51.9395 9.63855 52.59 10.3234 53.0478C11.0083 53.5055 11.8136 53.7499 12.6374 53.75C13.7423 53.7499 14.8019 53.311 15.5831 52.5297C16.3644 51.7484 16.8034 50.6888 16.8035 49.5839Z"
                                    fill="#3395FF"
                                />
                                <path
                                    d="M63.2612 29.1389V37.1572H0V26.7433H33.695L33.7061 26.7378C35.084 29.3547 37.1005 31.5812 39.5685 33.2109C42.0364 34.8406 44.876 35.8206 47.8238 36.0601C50.7716 36.2995 53.7321 35.7907 56.4308 34.5808C59.1295 33.3709 61.4789 31.499 63.2612 29.1389Z"
                                    fill="#1F2849"
                                />
                                <path
                                    d="M59.09 11.1362V20.2105C59.09 22.3747 57.643 25.1076 55.8573 26.3247L49.3906 30.7345L49.3659 6.39734L49.3604 6.3877L49.3906 6.37256L59.09 11.1362Z"
                                    fill="#1F2849"
                                />
                                <path
                                    d="M49.3654 6.39733L49.3902 30.7345L42.9249 26.3247C41.1392 25.1076 39.6909 22.361 39.6909 20.2105V11.1224L49.3599 6.3877L49.3654 6.39733ZM33.7061 26.7377L33.695 26.7432H0V22.5069C0.000219027 21.0092 0.595293 19.5728 1.65436 18.5138C2.71342 17.4547 4.14976 16.8596 5.64751 16.8594H31.7235L31.7703 16.8635C31.7166 17.4211 31.6896 17.9861 31.6891 18.5583C31.6844 21.4085 32.3769 24.2165 33.7061 26.7377Z"
                                    fill="#3395FF"
                                />
                                <path
                                    d="M22.5225 45.418C23.3465 45.418 24.152 45.6623 24.8371 46.1201C25.5222 46.5779 26.0562 47.2285 26.3715 47.9898C26.6868 48.751 26.7694 49.5887 26.6086 50.3968C26.4479 51.205 26.0511 51.9473 25.4684 52.53C24.8858 53.1126 24.1435 53.5094 23.3353 53.6701C22.5272 53.8309 21.6895 53.7484 20.9282 53.433C20.167 53.1177 19.5163 52.5837 19.0586 51.8986C18.6008 51.2135 18.3564 50.4081 18.3564 49.5841C18.3563 49.0369 18.464 48.4952 18.6733 47.9896C18.8827 47.4841 19.1895 47.0248 19.5764 46.6379C19.9633 46.2511 20.4226 45.9442 20.9281 45.7349C21.4336 45.5255 21.9754 45.4179 22.5225 45.418Z"
                                    fill="#F4F8FF"
                                />
                                <path
                                    d="M12.6374 45.418C13.4614 45.4177 14.267 45.6618 14.9523 46.1194C15.6376 46.577 16.1718 47.2276 16.4873 47.9888C16.8029 48.7501 16.8856 49.5878 16.725 50.396C16.5644 51.2042 16.1677 51.9467 15.5851 52.5295C15.0025 53.1122 14.2602 53.5092 13.452 53.67C12.6439 53.8309 11.8061 53.7485 11.0448 53.4332C10.2834 53.1179 9.63271 52.5839 9.17487 51.8988C8.71703 51.2136 8.47266 50.4081 8.47266 49.5841C8.47277 48.4794 8.91154 47.42 9.69251 46.6388C10.4735 45.8576 11.5327 45.4184 12.6374 45.418Z"
                                    fill="#1F2849"
                                />
                            </svg>
                        </div>
                        <div class="title-text">
                            <div>Keep card saved for future payments</div>
                        </div>
                        <div class="small-separator"></div>
                    </div>
                    <div>
                        <div class="content">
                            <p>
                                As per RBI’s latest guidelines, your permission is required
                                to save this card securely for
                                <?php
                                $a = 4;
                                if($input['recurring'] === '1'):
                                    echo "recurring";
                                else:
                                    echo "subscription";
                                endif;
                                ?>
                                payments
                                <?php
                                $a = 4;
                                if($merchantName):
                                    echo "on $merchantName";
                                endif;
                                ?>.
                            </p>
                            <p>
                                This is required to automatically charge your card
                                <?php
                                $a = 4;
                                if($input['recurring'] === '1'):
                                    echo "for recurring payments.";
                                else:
                                    echo "for future payments on this subscription.";
                                endif;
                                ?>
                            </p>
                            <br />
                            <p>
                    <span>
                      <span class="icon shield">
                        <svg
                            width="16"
                            height="17"
                            viewBox="0 0 16 17"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                          <path
                              d="M14.318 2.69624L14.3149 2.69635C10.4835 2.82577 8.34235 1.00465 8.25723 0.930958C8.16321 0.846648 8.04137 0.8 7.91505 0.8C7.78859 0.8 7.66662 0.846748 7.57257 0.93123C7.48291 1.00898 5.34818 2.82562 1.51521 2.69632L1.51211 2.69627C1.39148 2.69407 1.27395 2.7345 1.1802 2.81044C1.08645 2.88638 1.02249 2.99295 0.99959 3.11141L0.999591 3.11141C0.977901 3.22256 0.50393 5.87901 1.098 8.80585C1.69903 11.767 3.40269 15.0331 7.78928 16.1841L7.78928 16.1841L7.79287 16.185C7.87521 16.205 7.96114 16.205 8.04347 16.185L8.04348 16.185L8.04708 16.1841C12.4321 15.0331 14.135 11.7669 14.7352 8.8058C15.3212 5.9152 14.8656 3.28709 14.8305 3.11141L14.8305 3.1114C14.8076 2.99295 14.7436 2.88638 14.6499 2.81044C14.5561 2.7345 14.4386 2.69407 14.318 2.69624ZM7.915 15.1552C4.39377 14.1826 2.84534 11.7075 2.20273 9.25716C1.59535 6.94108 1.80263 4.65877 1.93392 3.72808C5.13862 3.71705 7.15007 2.51488 7.91512 1.96348C8.68188 2.51496 10.7098 3.71899 13.8906 3.72822C14.0082 4.65234 14.1943 6.93615 13.5814 9.25443C12.9314 11.7126 11.39 14.1936 7.915 15.1552Z"
                              fill="#01C413"
                              stroke="#01C413"
                              stroke-width="0.4"
                          />
                          <path
                              d="M7.19915 10.4837C7.26621 10.484 7.33266 10.471 7.39475 10.4457C7.4572 10.4202 7.51399 10.3826 7.56188 10.3351L7.56188 10.3351L7.56263 10.3344L10.0878 7.80297L10.088 7.80273C10.1835 7.7067 10.237 7.5768 10.237 7.4414C10.237 7.306 10.1835 7.1761 10.088 7.08008L10.0881 7.08003L10.0842 7.07632C9.98885 6.98545 9.86222 6.93477 9.73053 6.93477C9.59884 6.93477 9.47221 6.98545 9.37689 7.07632L9.37686 7.07628L9.37357 7.07956L7.19088 9.25932L6.45715 8.51511L6.45722 8.51505L6.45273 8.51077C6.35741 8.4199 6.23078 8.36921 6.09909 8.36921C5.96741 8.36921 5.84077 8.4199 5.74546 8.51077L5.74543 8.51074L5.74262 8.51353C5.69458 8.56117 5.65645 8.61786 5.63043 8.68031C5.60441 8.74277 5.59102 8.80975 5.59102 8.87741C5.59102 8.94507 5.60441 9.01206 5.63043 9.07452C5.65639 9.13682 5.6944 9.19339 5.74228 9.24097C5.74239 9.24108 5.74251 9.24119 5.74262 9.2413L6.83584 10.3345L6.83642 10.3351C6.88431 10.3826 6.9411 10.4202 7.00355 10.4457C7.06564 10.471 7.13209 10.484 7.19915 10.4837ZM7.19915 10.4837C7.19876 10.4837 7.19838 10.4837 7.19799 10.4837L7.19915 10.2837L7.20031 10.4837C7.19992 10.4837 7.19954 10.4837 7.19915 10.4837Z"
                              fill="#01C413"
                              stroke="#01C413"
                              stroke-width="0.4"
                          />
                        </svg>
                      </span>
                      <span>
                        All saved cards are compliant with the latest security
                        guidelines
                      </span>
                    </span>
                            </p>
                        </div>
                        <div class="actions">
                            <form
                                name="consentForm"
                                id="consentForm"
                                action="{{$url}}"
                                method="post"
                                onsubmit="return true;"
                            >
                                @foreach ($input as $key => $value)
                                    <input type="hidden" name="{{$key}}" value="{{$value}}" />
                                @endforeach
                                <input
                                    type="hidden"
                                    name="consent_to_save_card"
                                    id="consent_to_save_card"
                                    value="1"
                                />
                                <div class="submit-button-container">
                                    <button
                                        type="submit"
                                        id="submit-action"
                                        class="primary-button"
                                    >
                                        Secure & Continue
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div
                    class="powered-by no-desktop visible-sm"
                    style="justify-content: center;"
                >
                    <span>Powered by</span>
                    <img src="https://cdn.razorpay.com/logo.svg" alt="Razorpay" />
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function formatTime(seconds) {
        let minutesLeft = `${Math.floor(seconds / 60)}`;
        let secondsLeft = `${Math.floor(seconds % 60)}`;
        if (minutesLeft.length === 1) {
            minutesLeft = `0${minutesLeft}`;
        }
        if (secondsLeft.length === 1) {
            secondsLeft = `0${secondsLeft}`;
        }
        return `${minutesLeft}:${secondsLeft}`;
    }
    
    function disableButtons() {
        document.getElementById("submit-action").classList.add("disabled");
    }
    document
        .getElementById("submit-action")
        .addEventListener("click", disableButtons);
</script>
</body>
</html>
