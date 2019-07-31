<!doctype html>
<html>
  <head>
    <title>Error</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="user-scalable=no,width=device-width,initial-scale=1">
    <link rel="icon" href="https://razorpay.com/favicon.png" />
    <style>
      body {
        font-family: -apple-system, ubuntu, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
        color: #414141;
        background: #ecf0f1;
      }
      #failure path {
        fill: #e74c3c;
      }
      h3 {
        font-weight: normal;
      }
      .card {
        background: #fff;
        border-radius: 2px;
        box-shadow: 0 2px 9px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin: 30px auto;
        width: 80%;
        max-width: 300px;
        text-align: center;
      }
    </style>
  </head>
  <body>
  <div id="failure" class="card">
    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 16.538l-4.592-4.548 4.546-4.587-1.416-1.403-4.545 4.589-4.588-4.543-1.405 1.405 4.593 4.552-4.547 4.592 1.405 1.405 4.555-4.596 4.591 4.55 1.403-1.416z"/></svg>
    <h2>Error</h2>
    <p>
    @if (isset($data['error']) and isset($data['error']['description']))
      {{$data['error']['description']}}.
    @else
      An Error Occurred.
    @endif
      Please contact the merchant for assistance.
    </p>
  </div>
  </body>
</html>
