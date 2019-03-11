<html>
    <style>
        div#container {
            margin: auto;
            width: 50%;
            border: 3px solid green;
            padding: 10px;
        }

        #container table {
            margin: auto;
            width: 80%;
            padding: 10px;
        }

        #container thead td {
            margin: 0px auto;
            text-align: center;
            font-weight: bold;
            font-size: 1.1em;
        }

        .footer {
            margin: 0px auto;
            text-align: center;
        }
    </style>
    <body>
        <div id="container">
            <table>
                <thead>
                    <td colspan="2">TRANSACTION DETAILS</td>
                </thead>
                <tr>
                    <td>Razorpay Reference number</td>
                    <td>{{{ $data['razorpay_payment_id'] }}}</td>
                </tr>
                <tr>
                    <td>RRN</td>
                    <td>{{{ $data['rrn'] ?? "Not generated" }}}</td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td>{{{ date('Y/m/d') }}}</td>
                </tr>
                <tr>
                    <td>Transaction Status</td>
                    <td>
                        @if ($data['status'] === 'failed' or $data['status'] === 'created')
                            Failed
                        @else
                            Success
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        <br/>
        <div class="footer"><b>You may safely close this tab.</b></div>
    </body>
</html>