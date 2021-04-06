    html,
    body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
    }

    body {
        font-family: sans-serif;
        font-weight: normal;
        font-size: 17px;
        color: rgba(0, 0, 0, 0.6);
        min-height: 980px;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    body * {
        box-sizing: border-box;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        -o-box-sizing: border-box;
    }

    .foot-note {
        font-size: 13px;
        line-height: 20px;
        margin-top: 150px;
        bottom: 30px;
        left: 30px;
        right: 30px;
    }

    .foot-note ol {
        padding-left: 15px;
        font-size: 12px;
        color: rgba(0, 0, 0, 0.6);
        margin: 5px 0 0 0;
    }

    .bank-details {
        padding: 0 5px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        padding-bottom: 16px;
    }

    .bank-details span:first-child {
        width: 110px;
        display: inline-block;
    }

    .invoice-box {
        padding: 30px;
        border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0, 0, 0, .15);
        line-height: 24px;
        position: relative;
        height: 100%;
    }

    .invoice-box table {
        width: 100%;
        line-height: inherit;
        text-align: left;
        border-collapse: collapse;
    }

    .invoice-box table th {
        background-color: #f2f2f2 !important;
    }

    .invoice-box table td,
    .invoice-box table th {
        padding: 6px 10px;
        vertical-align: top;
    }

    .invoice-box table th {

        vertical-align: middle;
    }

    .invoice-box table td.sno {
        font-weight: 600;
        padding: 5px 12px;
    }

    .invoice-box table td.tax,
    .invoice-box table td.amount,
    .invoice-box table td.grand-total {

        white-space: nowrap;
    }

    .invoice-box table td.amount,
    .invoice-box table th.amount {
        padding-right: 15px;
    }

    .invoice-box table tr.top table td {
        padding-bottom: 20px;
    }

    .invoice-box table tr.top table td.title {
        font-size: 46px;
        line-height: 45px;
        color: #333;
    }

    .invoice-box table tr.top table td.title img.logo {

        display: block;
        width: 100%;
        max-width: 200px;
    }

    .invoice-box table tr.information table td {
        padding-bottom: 40px;
    }

    .invoice-box table th.heading td {
        background: #eee;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }

    .invoice-box table tr.details td {
        padding-bottom: 20px;
    }

    .invoice-box table tr.item td {
        border-bottom: 1px solid #eee;
    }

    .invoice-box table tr.item.last td {
        border-bottom: none;
    }

    .invoice-box table tr.total td {
        border-top: 2px solid #eee;
        font-weight: bold;
    }

    .invoice-box table tr.total td.empty {

        border-top: none;
    }

    .text-right {

        text-align: right;
    }

    .text-center {

        text-align: center;
    }

    .text-left {

        text-align: left !important;
    }

    .font-bold {

        font-weight: bold;
    }


    @media only print {

        body {

            font-size: 13px;
            line-height: 15px;
        }

        .invoice-box {

            padding: 15px;
        }

        .invoice-box table td {

            padding: 0 2px;
        }

        .invoice-box table th {

            padding-left: 0;
            padding-right: 0;
        }

        .invoice-box table tr.top table td {

            padding-bottom: 10px;
        }

        .invoice-box table tr.information table td {

            padding-bottom: 20px;
        }

        .invoice-box table td.sno,
        .invoice-box table th.sno {

            padding: 0 12px;
        }

        .foot-note {

            margin-bottom: 0;
            page-break-after: always;
        }
    }

    @media only screen and (max-width: 600px) {
        .invoice-box table tr.top table td {
            width: 100%;
            display: block;
            text-align: center;
        }

        .invoice-box table tr.information table td {
            width: 100%;
            display: block;
            text-align: center;
        }
    }

    .invoice-summary {
        font-weight: 600;
        font-size: 17px;
        line-height: 24px;
        color: rgba(0, 0, 0, 0.6);
    }

    .invoice-box table tr.information {
        border-top: 1px solid rgba(0, 0, 0, 0.1);
    }

    .invoice-box table tr.information td {
        padding: 6px 0;
    }

    .text-black-o-40 {
        color: rgba(0, 0, 0, 0.4);
    }

    .text-black-o-60 {
        color: rgba(0, 0, 0, 0.6);
    }

    .text-black-o-80 {
        color: rgba(0, 0, 0, 0.8);
    }

    .font-weight-600 {
        font-weight: 600;
    }

    .invoices-table tr:last-child {
        border-top: 1px solid rgba(0, 0, 0, 0.1);
    }

    .invoices-table .invoice-total-row{
        border-top: 1px solid rgba(0, 0, 0, 0.1);
    }

    .invoice-box .invoice-table-cnt {
        padding: 0;
    }

    .invoice-box .invoice-header,
    .invoice-box .invoice-header table td {
        padding: 0;
    }

    .invoices-table tr td:last-child {
        font-weight: 600;
    }
