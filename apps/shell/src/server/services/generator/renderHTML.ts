export const renderHTML = ({ title = 'Razorpay Dashboard', body = '' }) => {
  return `<html>
                <head>
                    <meta charSet="utf-8" />
                    <meta name="google" value="notranslate" />
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <meta name="author" content="Razorpay" />
                    <link rel="icon" type="image/png" href="https://razorpay.com/favicon.png" />
                    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
                    <title>${title}</title>
                </head>
                <body>
                    ${body}
                </body>
            </html>`;
};
