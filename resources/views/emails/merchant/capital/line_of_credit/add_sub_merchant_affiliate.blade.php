<!DOCTYPE html>
<html
    xmlns="http://www.w3.org/1999/xhtml"
>
<head>
    <title></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,400;0,600;0,800;1,700&display=swap"
          rel="stylesheet">
    <style type="text/css">
        body {
            font-family: 'Mulish', sans-serif !important;
        }

        .container {
            display: table;
            margin: 0px auto;
            background-color: #f3f3f3;
        }

        .main {
            max-width: 550px;
            background-color: #ffffff;
        }

        .imgeContainer {
            width: 100%;
        }

        .contents {
            margin: 30px 25px;
        }

        .textContainer {
            font-size: 18px;
            line-height: 20px;
            text-align: Left;
            font-weight: 400;
            padding: 0px 10px;
            color: #060606;
        }

        .paragraph {
            margin: 20px 0px;
        }

        .highlight {
            font-weight: 700;
        }

        .tableContainer {
            border-radius: 10px;
            width: 100%;
            background-color: #f2f2f2;
            margin: 50px 0px 50px;
        }

        .tableContents {
            display: flex;
        }

        .tableContentsMiddle {
            width: 100%;
        }

        .tableHeadBlue {
            background-color: #4297fc;
            border-radius: 10px 0px 0px 0px;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 18px !important;
            text-align: center !important;
            padding: 35px 45px !important;
        }

        .tableHeadGrey {
            background-color: #dbdbdb;
            border-radius: 0px 10px 0px 0px;
            color: #174078 !important;
            font-weight: 700;
            font-size: 18px !important;
            padding: 35px 45px !important;
        }

        .tableCell {
            padding: 25px 35px;
            width: 50%;
            text-align: left;
            font-size: 15px;
            color: #1a2145;
        }

        .tableBorderRight {
            border-right: 2px dashed #174078;;
        }

        .tableBodyLeft {
            background-color: #f5fbff;
        }

        .tableBodyRight {
            background-color: #f5f5f5;
        }

        .tableBodyMiddle {
            background-color: #174078;
            font-size: 18px;
            font-weight: 500;
            color: #ffffff;
            width: 100%;
            white-space: nowrap;
            padding: 15px 0px;
            text-align: center;
        }

        .tableBorderRadiusLeft {
            border-radius: 0px 0px 0px 10px;
            border-bottom: none;
        }

        .tableBorderRadiusRight {
            border-radius: 0px 0px 10px 0px;
            border-bottom: none;
        }

        .buttonContainer {
            display: table;
            margin: 40px auto;
        }

        .blueButtonLoc {
            background-color: #0d6fe6;
            color: #ffffff !important;
            font-style: none;
            text-decoration: none;
            padding: 20px 35px;
            text-align: center;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: 700;
        }

        .boldText {
            font-weight: 700;
            line-height: 20px;
            padding: 20px 10px;
        }

        .listContainer {
            margin: 20px 10px;
        }

        .listItemContainer {
            color: #23496d;
            font-size: 18px;
            display: flex;
            margin-bottom: 20px;
        }

        .listImageContainer {
            width: 15%;
        }

        .listImage {
            width: 90%;
        }

        .listDetailsContainer {
            padding: 5px;
            margin: 0px 0px 0px 5px;
        }

        .listDetailsBold {
            font-weight: 700;
        }

        .bottomContainer {
            margin: 40px auto;
            display: table;
            text-align: center;
            font-size: 16px;
        }

        .textBold {
            font-weight: bold;
        }

        .bottomContainerThanks {
            margin: 40px auto;
            display: table;
            text-align: center;
            font-size: 18px;
        }

        .textBoldBlue {
            font-weight: bold;
            color: #4297fc;
        }

        .footerContainer {
            background-color: #1a2145;
            color: #ffffff;
            width: 100%;
            display: table;
            margin: 0px auto;
            text-align: center;
        }

        .footerImage {
            margin: 15px 0px;
        }
    </style>
    <!--[if !mso]><!-->
    <style type="text/css">
        @media only screen and (max-width: 480px) {
            @-ms-viewport {
                width: 320px;
            }
            @viewport {
                width: 320px;
            }
        }
    </style>
    <style type="text/css"></style>
</head>
<body style="background-color: #f3f3f3">
<div class="container">
    <div class="main">
        <div class="imgeContainer">
            <img
                src="https://cdn.razorpay.com/static/assets/email/loc/loc_head_section.png"
                alt="header"
                class="imgeContainer"
            />
        </div>
        <div class="contents">
            <div class="textContainer">
                <div class="paragraph">Congratulations!</div>
                <div class="paragraph">
                    {{$merchant['name']}} has partnered with Razorpay to help you with your
                    short-term loan requirements.
                </div>
                <div class="paragraph">
                    Razorpay <span class="highlight">'Line of Credit'</span> powered
                    by Gromor Finance is made to help businesses like yours with their
                    planned & unplanned cash requirements.
                </div>
            </div>
            <div class="tableContainer">
                <div class="tableContents">
                    <div class="tableCell tableHeadBlue tableBorderRight">
                        Line of Credit
                    </div>
                    <div class="tableCell tableHeadGrey">Business Loan</div>
                </div>
                <div class="tableContentsMiddle">
                    <div class="tableBodyMiddle">Withdrawal fee</div>
                </div>
                <div class="tableContents">
                    <div class="tableCell tableBodyLeft tableBorderRight">
                        <img
                            src="https://cdn.razorpay.com/static/assets/email/loc/loc_tick.png"
                            alt="tick"
                            width="14"
                            style="margin: 0px 5px 0px 0px"
                        />
                        Withdraw as per need
                    </div>
                    <div class="tableCell tableBodyRight">Full disbursal</div>
                </div>
                <div class="tableContentsMiddle">
                    <div class="tableBodyMiddle">Daily interest</div>
                </div>
                <div class="tableContents">
                    <div class="tableCell tableBodyLeft tableBorderRight">
                        <img
                            src="https://cdn.razorpay.com/static/assets/email/loc/loc_tick.png"
                            alt="tick"
                            width="14"
                            style="margin: 0px 5px 0px 0px"
                        />
                        Pay as you use
                    </div>
                    <div class="tableCell tableBodyRight">
                        Interest on full amount
                    </div>
                </div>
                <div class="tableContentsMiddle">
                    <div class="tableBodyMiddle">Reduce interest flow</div>
                </div>
                <div class="tableContents">
                    <div class="tableCell tableBodyLeft tableBorderRight">
                        <img
                            src="https://cdn.razorpay.com/static/assets/email/loc/loc_tick.png"
                            alt="tick"
                            width="14"
                            style="margin: 0px 5px 0px 0px"
                        />
                        Pay early to save
                    </div>
                    <div class="tableCell tableBodyRight">Fixed interest</div>
                </div>
                <div class="tableContentsMiddle">
                    <div class="tableBodyMiddle">Easy to re-withdraw</div>
                </div>
                <div class="tableContents">
                    <div
                        class="tableCell tableBodyLeft tableBorderRight tableBorderRadiusLeft"
                    >
                        <img
                            src="https://cdn.razorpay.com/static/assets/email/loc/loc_tick.png"
                            alt="tick"
                            width="14"
                            style="margin: 0px 5px 0px 0px"
                        />
                        One-click draw
                    </div>
                    <div class="tableCell tableBodyRight tableBorderRadiusRight">
                        New application
                    </div>
                </div>
            </div>
            <div class="buttonContainer">
                @if($token)
                    <a
                        type="button"
                        href="{{'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST)
                                .'/forgot-password#token='. $token
                                . '&email=' . $subMerchant['email']}}"
                        class="blueButtonLoc">
                        Apply Now
                    </a>
                @else
                    <a
                        type="button"
                        href="{{'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST)}}"
                        class="blueButtonLoc">
                        Apply Now
                    </a>
                @endif
            </div>
            <div class="textContainer">
                <div class="boldText">
                    Get a collateral-free credit line and withdraw cash 24*7 for your
                    business needs.
                </div>
            </div>
            <div class="listContainer">
                <div class="listItemContainer">
                    <div class="listImageContainer">
                        <img src="https://cdn.razorpay.com/static/assets/email/loc/credit_limit.png" class="listImage"/>
                    </div>
                    <div class="listDetailsContainer">
                        <div class="listDetailsBold">Credit Limit up to Rs 50 L:</div>
                        <div class="listDetails">
                            100% flexibility & no preclosure charges
                        </div>
                    </div>
                </div>

                <div class="listItemContainer">
                    <div class="listImageContainer">
                        <img src="https://cdn.razorpay.com/static/assets/email/loc/low_interest.png" class="listImage"/>
                    </div>
                    <div class="listDetailsContainer">
                        <div class="listDetailsBold">Low interest rates:</div>
                        <div class="listDetails">Starting from 1.5% per month</div>
                    </div>
                </div>

                <div class="listItemContainer">
                    <div class="listImageContainer">
                        <img src="https://cdn.razorpay.com/static/assets/email/loc/reduce_interest.png"
                             class="listImage"/>
                    </div>
                    <div class="listDetailsContainer">
                        <div class="listDetailsBold">Reduce interest flow:</div>
                        <div class="listDetails">Pay as you use</div>
                    </div>
                </div>
            </div>
            <div class="textContainer" style="margin:40px 0px">
                <div class="paragraph">
                    To know more, simply click on the link below and submit your
                    application.
                </div>
            </div>
            <div class="buttonContainer">
                <a type="button" href='https://razorpay.com/x/line-of-credit/' class="blueButtonLoc">Learn More</a>
            </div>
            <div class="bottomContainer">
                <div>For assistance, reach out to us at</div>
                <div class="textBold">capital.support@razorpay.com</div>
            </div>
            <div class="bottomContainerThanks">
                <div>Thanks & Regards,</div>
                <div class="textBoldBlue">Razorpay Partnerships Team</div>
            </div>
        </div>
        <div class="footerContainer">
            <img
                src="https://cdn.razorpay.com/static/assets/email/loc/rzp_white_logo.png"
                alt=" Razorpay"
                height="25"
                class="footerImage"
            />
        </div>
    </div>
</div>
</body>
</html>
