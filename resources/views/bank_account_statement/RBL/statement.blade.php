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
            <img class="logo" src="https://drws17a9qx558.cloudfront.net/website/images/logo.png" style="width:180px;height: 75px;">
        </span>
    </header>

    <div class="box">
        <table class="w-100 details-table details-table1">
            <tr>
                <td>
                    <span>Account Name: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['account_name']}}</b>
                </td>
                <td>
                    <span>Home Branch: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['home_branch_name']}}</b>
                </td>
            </tr>
            <tr>
                <td>
                    <span>Customer Address: </span>
                </td>
                <td>
                    <b> {{$account_owner_info['customer_address']}} <br>
                        {{$account_owner_info['customer_address_l2']}} <br>
                        {{$account_owner_info['customer_city']}} {{$account_owner_info['customer_address_pin']}} <br>
                        {{$account_owner_info['customer_state']}} <br>
                        {{$account_owner_info['customer_country']}}</b>
                </td>
                <td>
                    <span>Home Branch Address: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['home_branch_address']}}</b>
                </td>
            </tr>
            <tr>
                <td>
                    <span>Phone: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['customer_mobile']}}</b>
                </td>
                <td>
                    <span>IFSC/RTGS/NEFT code: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['ifsc_code']}}</b>
                </td>
            </tr>
            <tr>
                <td>
                    <span>Email Id: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['customer_email']}}</b>
                </td>
                <td>
                    <span>Sanction Limit: </span>
                </td>
                <td>
                    <b>@include('bank_account_statement.RBL.currency',[
                        'value' => $account_owner_info['sanction_limit'],
                        'currency' => $account_owner_info['currency'],
                    ])</b>
                </td>
            </tr>
            <tr>
                <td>
                    <span>CIF ID: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['customer_cif_id']}}</b>
                </td>
                <td>
                    <span>Drawing Power: </span>
                </td>
                <td>
                    <b> @include('bank_account_statement.RBL.currency',[
                       'value' => $account_owner_info['drawing_power'],
                       'currency' => $account_owner_info['currency'],
                   ])</b>
                </td>
            </tr>
            <tr>
                <td>
                    <span>A/C Currency: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['currency']}}</b>
                </td>
                <td>
                    <span>Branch Timings: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['branch_timings']}}</b>
                </td>
            </tr>
            <tr>
                <td>
                    <span>A/C Opening Date: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['account_opening_date']}}</b>
                </td>
                <td>
                    <span>Call Center:: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['call_center']}}</b>
                </td>
            </tr>
            <tr>
                <td>
                    <span>A/C Type: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['account_type']}}</b>
                </td>
                <td>
                    <span>Branch Phone Num: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['branch_phone_number']}}</b>
                </td>
            </tr>
            <tr>
                <td>
                    <span>A/c Status: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['account_status']}}</b>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <span>Statement Of Transactions in Account Number: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['account_number']}}</b>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <span>Period: </span>
                </td>
                <td>
                    <b>{{$account_owner_info['statement_period']}}</b>
                </td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>

    <div class="box">

        <div class="box-header transactions-title">
            Transactions List - {{ $account_owner_info['account_name'] }} ( {{ $account_owner_info['currency'] }} ) - {{ $account_owner_info['account_number'] }}
        </div>
        <table class="w-100 transactions-table">
            <tr class='transactions-table-header'>
                <th class="text-left">
                    Transaction Date
                </th>
                <th class="text-left">
                    Transaction Details
                </th>
                <th class="text-left">
                    Cheque ID
                </th>
                <th class="text-left">
                    Value Date
                </th>
                <th class="text-right">
                    Withdrawal Amt ({{ $account_owner_info['currency'] }})
                </th>
                <th class="text-right">
                    Deposit Amt ({{ $account_owner_info['currency'] }})
                </th>
                <th class="text-right">
                    Balance ({{ $account_owner_info['currency'] }})
                </th>
            </tr>
            @foreach($transactions as $transaction)
                <tr>
                    <td class="text-left">
                        {{ $transaction['transaction_date'] }}
                    </td>
                    <td class="text-left">
                        {{ $transaction['transaction_details'] }}
                    </td>
                    <td  class="text-left">
                        {{ $transaction['cheque_id'] }}
                    </td>
                    <td  class="text-left">
                        {{ $transaction['value_date'] }}
                    </td>
                    <td class="text-right">
                        {{ $transaction['withdrawal_amount'] }}
                    </td>
                    <td class="text-right">
                        {{ $transaction['deposit_amount'] }}
                    </td>
                    <td class="text-right">
                        {{ $transaction['balance'] }}
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
                <td>
                    <span>Lien Amt:</span>
                </td>
                <td>
                    <b>{{$account_owner_info['currency']}} {{ $statement_summary['lien_amount'] }}</b>
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

    <div class="box">
        <div class="heading">Important Information</div>
        <div class="p-20">
            <span style="color: #c60f13; font-weight: bold; margin-right: 5px">Commonly Used Abbreviations:</span><b>OFT</b> - RBL Own account transfer, <b>TPFT</b> - RBL to Another Bank account, <b>ATW</b> - Cash withdrawl from RBL Bank ATM,<br>
            VAT/AT/NFS -Cash Withdrawl from other Bank ATM, <b>ATW</b> - Domestic ATM Transactions, <b>ATI</b> - International ATM Transaction, <b>PCD</b> - Domestic Point<br>
            of Sale Transaction, <b>PCI</b> - International Point of Sale Transaction, <b>AFT</b> ATM Fund Transfer, <b>ATR</b> - Domestic/International ATM transaction reversal,<br>
            <b>PCR</b> - Domestic/International POS transaction reversal.
            <br>
            <br>
            <div class="text-center" style="color: #c60f13;font-style: italic">** End of Statement**</div>
        </div>
    </div>
</main>
</body>
</html>
