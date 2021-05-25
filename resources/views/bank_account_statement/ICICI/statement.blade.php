<html>
<style>
    .text-left {
        text-align: left;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .w-25 {
        width: 25%;
    }

    .transactions-table-header th
    {
        padding: 10px 5px 10px 5px;
        background-color: #E5E5E5;
        color: black;
        font-size: 12px;
        line-height: 12px
    }

    img
    {
        opacity: 1;
    }

    .w-50 {
        width: 50%;
    }

    .w-75 {
        width: 75%;
    }

    .w-100 {
        width: 100%;
    }

    .p-20 {
        padding: 20px;
    }

    .box {
        margin: 20px 0;
        width: 100%;
        border: 1px solid #d9d9d9;

    }

    .box-header {
        padding: 20px;
        background-color: #616161;
        font-weight: bold;
        color: white;
        font-family: Arial;
        font-style: normal;
        font-weight: bold;
        font-size: 16px;
        line-height: 20px;
    }

    main {
        padding: 20px;
    }

    .font-bold {
        font-weight: bold;
    }

    .logo {
        height: 50px;
    }

    .details-table td {
        text-align: center;
        width: 50%;
        padding: 10px;
    }

    .heading{
        background: #232870;
        color: white;
        border-radius: 10px;
        margin: 5px 10px;
        padding: 18px;
        font-size: 22px;
        font-weight: bold;
        padding-left: 20px;
    }

    .transactions-table tr:nth-child(2n+1) {
        background: #f2f2f2;
    }

    .transactions-table tr:first-child {
        background-color: darkgray;
    }

    .transactions-table td {
        padding: 10px;
        color: rgba(0, 0, 0);
        font-family: 'Arial';
        font-weight: normal;
    }

    .transactions-table {
        border-collapse: collapse;
        border-spacing: 0;
        font-family: Arial;
        font-style: normal;
        font-weight: 600;
        font-size: 14px;
        line-height: 12px;
    }

    .details-table td:nth-child(odd) {
        text-align: right;
        font-family: Arial;
        font-style: normal;
        font-size: 16px;
        line-height: 18px;
        width: 22%;
        color: rgba(0, 0, 0);
    }

    .details-table td:nth-child(even) {
        text-align: left;
        font-family: Arial;
        font-style: normal;
        font-weight: normal;
        font-size: 16px;
        line-height: 18px;
        width: 30%;
        color: rgba(0, 0, 0);
    }

    .summary
    {
        background-color: rgba(0, 0, 0, 0.8);
        color: rgba(255, 255, 255);
        letter-spacing: 0.5px;
    }

    .transactions-title {
        background-color: rgba(0, 0, 0, 0.8);
        color: rgba(255, 255, 255);
        font-family: Arial;
        font-style: normal;
        font-weight: bold;
        font-size: 16px;
        line-height: 20px;
        letter-spacing: 0.5px;
        width: 100%;
        overflow: visible;
        box-sizing: border-box;
    }

    .account-statement-title
    {
        font-family: Arial;
        font-weight: bold;
        font-size: 24px;
        color: rgba(0, 0, 0, 0.6);
        padding-top: 12px;
        padding-bottom: 4px;
    }

</style>

<body>
<main>

    <header class="text-right">
        <span>
            <img class="logo" src="https://cdn.razorpay.com/static/assets/razorpayx/logos/rx-dark-logo.png" style="padding-bottom: 14px;padding-right: 30px;">
            <img class="logo" src="https://cdn.razorpay.com/static/assets/razorpayx/banking-account-statement/icici_logo.jpg" style="width:194px;height:80px;">
        </span>
    </header>

    <div class="box">

        <div class="box-header transactions-title">
            Transactions List - {{ $account_owner_info['account_name'] }} ( {{ $account_owner_info['currency'] }} ) - {{ $account_owner_info['account_number'] }}
        </div>
        <table class="w-100 transactions-table">
            <tr class='transactions-table-header'>
                <th class="text-left">
                    No.
                </th>
                <th class="text-left">
                    Transaction ID
                </th>
                <th class="text-center">
                    Value Date
                </th>
                <th class="text-center">
                    Txn Posted Date
                </th>
                <th class="text-left">
                    ChequeNo.
                </th>
                <th class="text-left">
                    Description
                </th>
                <th class="text-right">
                    CR/DR
                </th>
                <th class="text-right">
                    Transaction Amount ({{ $account_owner_info['currency'] }})
                </th>
                <th class="text-right">
                    Available Balance ({{ $account_owner_info['currency'] }})
                </th>
            </tr>
            @foreach($transactions as $transaction)
                <tr>
                    <td class="text-left">
                        {{ $transaction['no'] }}
                    </td>
                    <td class="text-center">
                        {{ $transaction['transaction_id'] }}
                    </td>
                    <td  class="text-center">
                        {{ $transaction['value_date'] }}
                    </td>
                    <td  class="text-center">
                        {{ $transaction['transaction_posted_date'] }}
                    </td>
                    <td class="text-left">
                        {{ $transaction['cheque_no'] }}
                    </td>
                    <td class="text-left">
                        {{ $transaction['description'] }}
                    </td>
                    <td class="text-center">
                        {{ $transaction['cr_dr'] }}
                    </td>
                    <td class="text-right">
                        {{ $transaction['transaction_amount'] }}
                    </td>
                    <td class="text-right">
                        {{ $transaction['available_balance'] }}
                    </td>
                </tr>
            @endforeach

        </table>
    </div>

    <div class="box">
        <div class="box-header summary">
            Statement Summary
        </div>
        <table class="w-100 details-table">
            <tr>
                <td>
                    <span>Opening Balance:</span>
                </td>
                <td>
                    <b>{{$account_owner_info['currency']}} {{ $statement_summary['opening_balance'] }}</b>
                </td>
                <td>
                    <span>Count Of Debit:</span>
                </td>
                <td>
                    <b>{{ $statement_summary['debit_count'] }}</b>
                </td>

            </tr>
            <tr>
                <td>
                    <span>Closing Balance:</span>
                </td>
                <td>
                    <b>{{$account_owner_info['currency']}} {{ $statement_summary['closing_balance'] }}</b>
                </td>
                <td>
                    <span>Count Of Credit:</span>
                </td>
                <td>
                    <b>{{ $statement_summary['credit_count'] }}</b>
                </td>
            </tr>
            <tr>
                <td>
                    <span>Eff Avail Bal::</span>
                </td>
                <td>
                    <b>{{$account_owner_info['currency']}} {{ $statement_summary['effective_balance'] }}</b>
                </td>
            </tr>
            <t>
                <td>(As On:</td>
                <td>
                    <b>{{ $statement_summary['statement_generated_date'] }}</b>   )
                </td>
            </t>
        </table>
    </div>

</main>
</body>
</html>
