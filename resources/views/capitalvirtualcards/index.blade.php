<html>
  <head>
    <title>RazorpayX Corporate Card</title>
    <meta name="description" content="Virtual Card details including card number and expiry" />
    <meta name="viewport" content="width=device-width" />
    <meta charset="utf-8" />
      <link rel="preconnect" href="https://cdn.razorpay.com">
      <style>
          @import url('https://fonts.googleapis.com/css2?family=Lato&display=swap');
          @font-face {
              font-family: 'OCRB';
              src: url('https://cdn.razorpay.com/static/assets/common/capital/OCRB.woff2') format('woff2');
              font-style: normal;
          }
          @font-face {
              font-family: 'Forza';
              src: url('https://cdn.razorpay.com/static/assets/common/capital/Forza.woff2') format('woff2');
              font-style: normal;
          }
          * {
              box-sizing: border-box;
          }
          body {
              font-family: Lato, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu,
              Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
              font-size: 14px;
              margin: 0;
              background: #000;
          }
          #root {
              margin: 30px auto;
              background: #000;
          }
      </style>
  </head>
  <body>
    <div id="root"></div>
  </body>
  <script src="{{env('AWS_CF_CDN_URL')}}/capital/virtual-card/main.js" async></script> 
</html>
