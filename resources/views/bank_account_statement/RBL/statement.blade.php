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
        border: 1px solid #e3e3e3;
    }
    .box-header{
        padding: 20px;
        background-color: darkgrey;
    }
    main{
        padding: 20px;
    }
    .font-bold {
        font-weight: bold;
    }

    .logo{
        height: 50px;
    }

    .details-table td {
        text-align: center;
        width: 50%;
        padding: 10px;
    }

    .transactions-table tr:nth-child(2n+1) {
        background: #c9c8c8;
    }
    .transactions-table tr:first-child {
        background-color: darkgray;
    }
    .transactions-table td {
        text-align: left;
        padding: 10px;
    }

    .transactions-table{
        border-spacing: unset;
    }

    .details-table td:nth-child(1) {
        width: 10%;
    }

    .details-table td:nth-child(2) {
        width: 30%;
    }
    .details-table td:nth-child(3) {
        width: 10%;
    }
    .details-table td:nth-child(4) {
        width: 30%;
    }

    .transactions-title{
        background-color: #616161;
        color: white;
    }



</style>
<body>
<main>
    <header class="text-right">
        <img class="logo" src="https://drws17a9qx558.cloudfront.net/website/images/logo.png" />
    </header>

    <div class="box">
        <table class="w-100 details-table details-table1">
            <tr>
                <td>
                    <span>Account Name: </span>
                </td>
                <td>
                    <strong>{{$account_owner_info['account_name']}}</strong>
                </td>
                <td>
                    <span>Home Branch: </span>
                </td>
                <td>
                    <strong> {{$account_owner_info['home_branch_name']}} </strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span>Customer Address: </span>
                </td>
                <td>
                    <strong> {{$account_owner_info['customer_address']}} </strong><br>
                    <strong> {{$account_owner_info['customer_address_l2']}} </strong>
                    <strong> {{$account_owner_info['customer_city']}} </strong>
                    <strong> {{$account_owner_info['customer_state']}} </strong>
                </td>
                <td>
                    <span>Home Branch Address: </span>
                </td>
                <td>
                    <strong> {{$account_owner_info['home_branch_address']}} </strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span>Phone: </span>
                </td>
                <td>
                    <strong>{{$account_owner_info['customer_mobile']}}</strong>
                </td>
                <td>
                    <span>IFSC/RTGS/NEFT code: </span>
                </td>
                <td>
                    <strong> {{$account_owner_info['ifsc_code']}} </strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span>Email Id: </span>
                </td>
                <td>
                    <strong>{{$account_owner_info['customer_mobile']}}</strong>
                </td>
                <td>
                    <span>Sanction Limit: </span>
                </td>
                <td>
                    <strong> {{$account_owner_info['sanction_limit']}} </strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span>CIF ID: </span>
                </td>
                <td>
                    <strong>{{$account_owner_info['customer_cif_id']}}</strong>
                </td>
                <td>
                    <span>Drawing Power: </span>
                </td>
                <td>
                    <strong> {{$account_owner_info['drawing_power']}} </strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span>A/C Currency: </span>
                </td>
                <td>
                    <strong>{{$account_owner_info['currency']}}</strong>
                </td>
                <td>
                    <span>Branch Timings: </span>
                </td>
                <td>
                    <strong> {{$account_owner_info['branch_timings']}} </strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span>A/C Opening Date: </span>
                </td>
                <td>
                    <strong>{{$account_owner_info['account_opening_date']}}</strong>
                </td>
                <td>
                    <span>Call Center:: </span>
                </td>
                <td>
                    <strong> {{$account_owner_info['call_center']}} </strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span>A/C Type: </span>
                </td>
                <td>
                    <strong>{{$account_owner_info['account_type']}}</strong>
                </td>
                <td>
                    <span>Branch Phone Num: </span>
                </td>
                <td>
                    <strong> {{$account_owner_info['branch_phone_number']}} </strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span>A/c Status: </span>
                </td>
                <td>
                    <strong>{{$account_owner_info['account_status']}}</strong>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <span>Statement Of Transactions in Account Number: </span>
                </td>
                <td>
                    <strong>{{$account_owner_info['account_number']}}</strong>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <span>Period: </span>
                </td>
                <td>
                    <strong>{{$account_owner_info['statement_period']}}</strong>
                </td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="box-header transactions-title">
            Transactions List - {{ $account_owner_info['account_name'] }} ({{  $account_owner_info['currency'] }}) - {{ $account_owner_info['account_number'] }}
        </div>
        <table class="w-100 transactions-table">
            <tr>
                <th>
                    Transaction Date
                </th>
                <th>
                    Transaction Details
                </th>
                <th>
                    Cheque ID
                </th>
                <th>
                    Value Date
                </th>
                <th>
                    Withdrawl Amt({{ $account_owner_info['currency'] }})
                </th>
                <th>
                    Deposit Amt ({{ $account_owner_info['currency'] }})
                </th>
                <th>
                    Balance ({{ $account_owner_info['currency'] }})
                </th>
            </tr>
            @foreach($transactions as $transaction)
            <tr>
                <td>
                    {{ $transaction['transaction_date'] }}
                </td>
                <td>
                    NEFT/000077972351/Invalid CPIN
                </td>
                <td>
                    1234567RYYTY
                </td>
                <td>
                    21/05/2019
                </td>
                <td>
                    1.00
                </td>
                <td>
                    0
                </td>
                <td>
                    1276532.11
                </td>
            </tr>
            @endforeach

        </table>
    </div>

    <div class="box">
        <div class="box-header">
            Statement Summary
        </div>
        <table class="w-100 details-table">
            <tr>
                <td>
                    <span>Opening Balance:</span>
                </td>
                <td>
                    <strong> {{$account_owner_info['currency']}} {{ $statement_summary['opening_balance'] }}</strong>
                </td>
                <td>
                    <span>Count Of Debit:</span>
                </td>
                <td>
                    <strong>{{ $statement_summary['debit_count'] }}</strong>
                </td>

            </tr>
            <tr>
                <td>
                    <span>Closing Balance:</span>
                </td>
                <td>
                    <strong>{{$account_owner_info['currency']}} {{ $statement_summary['closing_balance'] }}</strong>
                </td>
                <td>
                    <span>Count Of Credit:</span>
                </td>
                <td>
                    <strong>{{ $statement_summary['credit_count'] }}</strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span>Eff Avail Bal::</span>
                </td>
                <td>
                    <strong>{{$account_owner_info['currency']}} {{ $statement_summary['effective_balance'] }}</strong>
                </td>
                <td>
                    <span>Lien Amt:</span>
                </td>
                <td>
                    <strong>{{$account_owner_info['currency']}} {{ $statement_summary['lien_amount'] }}</strong>
                </td>
            </tr>
            <t>
                <td>As On:</td>
                <td>
                    <strong>{{ $statement_summary['statement_generated_date'] }}</strong>
                </td>
            </t>
        </table>
    </div>

    <div class="box">
        <div class="box-header">
            Important Information
        </div>
        <div class="p-20">
            <p>
                Lorem ipsum dolor, sit amet consectetur adipisicing elit. Labore quia, reiciendis molestiae iure incidunt eligendi itaque praesentium tenetur pariatur quis aliquid earum placeat accusamus atque alias quos, quae corrupti. Laborum.
            </p>
            <ul>
                <li>Lorem ipsum dolor sit amet consectetur adipisicing elit. Autem facere hic dolore, ipsam architecto dolorum reprehenderit, distinctio illo nam libero, sint quo debitis. Delectus, officiis. Culpa totam voluptatibus dolor sint?</li>
                <li>Lorem ipsum dolor sit amet consectetur adipisicing elit. Autem facere hic dolore, ipsam architecto dolorum reprehenderit, distinctio illo nam libero, sint quo debitis. Delectus, officiis. Culpa totam voluptatibus dolor sint?</li>
                <li>Lorem ipsum dolor sit amet consectetur adipisicing elit. Autem facere hic dolore, ipsam architecto dolorum reprehenderit, distinctio illo nam libero, sint quo debitis. Delectus, officiis. Culpa totam voluptatibus dolor sint?</li>
            </ul>
            <p>
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Hic soluta voluptates dignissimos sit placeat repellendus dolore officiis in amet. Asperiores rem impedit, pariatur ducimus assumenda est! Quasi laudantium vitae soluta.
            </p>
        </div>
    </div>
</main>
</body>
</html>
