<html lang="en">
   <head>
      <title></title>
   </head>
   <body>
      <main>
         <div class="container">
            <h1 class="center">Pricing Plan</h1>
            <h4>The following transaction fees will be levied for your transactions initiated via partner platform you are onboarding</h4>
            <h5>Last updated on Oct 3, 2024</h5>
            <p> We acknowledge and agree that the Razorpay Fees applicable for transactions initiated through OAuth shall be as provided hereinbelow:
         </div>
         <table style="border: 1px solid grey;">
            <thead>
               <tr style="border: 1px solid grey">
                  <th style="border: 1px solid grey">Payment Method</th>
                  <th style="border: 1px solid grey">Percent Rate</th>
                  <th style="border: 1px solid grey">Fixed Rate</th>
                  <th style="border: 1px solid grey">Min Fee</th>
                  <th style="border: 1px solid grey">Max Fee</th>
                  <th style="border: 1px solid grey">Amount Range Max</th>
                  <th style="border: 1px solid grey">Amount Range Min</th>
                  <th style="border: 1px solid grey">Amount Range Active</th>
                  <th style="border: 1px solid grey">Fee Bearer</th>
                  <th style="border: 1px solid grey">Amount Model</th>
                  <th style="border: 1px solid grey">Channel</th>
                  <th style="border: 1px solid grey">Expired At</th>
                  <th style="border: 1px solid grey">Payment Method SubType</th>
                  <th style="border: 1px solid grey">Payment Issuer</th>
               </tr>
            </thead>
            <tbody>
               @foreach ($data['rules'] as $rules)
               <tr>
                  <td>{{ $rules['payment_method'] }}</td>
                  <td>{{ $rules['percent_rate'] }}</td>
                  <td>{{ $rules['fixed_rate'] }}</td>
                  <td>{{ $rules['min_fee'] }}</td>
                  <td>{{ $rules['max_fee'] }}</td>
                  <td>{{ $rules['amount_range_max'] }}</td>
                  <td>{{ $rules['amount_range_min'] }}</td>
                  <td>{{ $rules['amount_range_active'] }}</td>
                  <td>{{ $rules['fee_bearer'] }}</td>
                  <td>{{ $rules['amount_model'] }}</td>
                  <td>{{ $rules['channel'] }}</td>
                  <td>{{ $rules['expired_at'] }}</td>
                  <td>{{ $rules['payment_method_subtype'] }}</td>
                  <td>{{ $rules['payment_issuer'] }}</td>
               </tr>
               @endforeach
            </tbody>
         </table>
      </main>
   </body>
</html>

