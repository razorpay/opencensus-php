export const THUMBNAIL_SIZE_LIMIT = 1024 * 1024;
export const VIEWPORT = { width: 380, height: 60 };
export const BOUNDARY = { width: '100%', height: 200 };
export const SUCCESS = 'success';
export const ERROR = 'error';
export const UPLOADING_IMAGE = 'Uploading image...';
export const CLOSE_TIMEOUT = 2500;
export const FILE_UPLOADED_SUCCESSFULLY = 'File Uploaded Successfully';
export const LOGO = 'logo';
export const FILE_TYPES = ['png', 'jpg', 'jpeg'];
export const UPLOAD_IMAGE_HERE = 'Upload Image here';
export const LOGO_REMOVED_SUCCESSFULLY = 'Logo Removed Successfully';
export const BRAND_LOGO_SIZE_LIMIT = 1048576;
export const BRAND_LOGO_RATIO_INFO = 'Recommended aspect ratio 1:1';
export const BRAND_COLOR_INFO = 'Choose a theme color for the receipt header';
export const RECEIPT_PREVIEW_INFO = 'This is a preview of how your payment receipt will appear to customers';
export const RECEIPT_CUSTOMIZATION_SUCCESS = 'Payment receipt customization saved successfully';
export const RECEIPT_CUSTOMIZATION_ERRORS = {
    FILE_SIZE_ERROR: `Logo size exceeds the limit of ${BRAND_LOGO_SIZE_LIMIT/1024/1024} MB`,
    FILE_TYPE_ERROR: 'Invalid file type. Please upload a PNG, JPG, or JPEG file',
    FILE_READ_ERROR: 'Failed to read the selected file',
    LOAD_ERROR: 'Failed to load customization settings',
    SAVE_ERROR: 'Failed to save customization settings',
};
//TODO: Will fetch this from templating service render API
export const PAYMENT_RECEIPT_CUSTOM_TEMPLATE =
`<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Payment Receipt</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        body { margin: 0; padding: 0; background-color: #F0F0F0; font-family: 'Trebuchet MS', sans-serif; color: #000000; }
        img { border: 0; max-width: 100%; }

        .background-split { height: 100vh; width: 100%; position: absolute; top: 0; left: 0; z-index: -1; }

        .background-split::before, .background-split::after { content: ''; display: block; width: 100%; }

        .background-split::before { height: 30vh; background-color: {{brand_color}}; }

        .background-split::after { height: 75vh; background-color: #F0F0F0; }

        .container { width: 90%; max-width: 500px; min-width: 320px; margin: 0 auto; }
        
        .card { background-color: #fff; padding: 16px 20px; margin-top: 8px; box-sizing: border-box; width: 100%; }
        
        .header { padding-top: 16px; }
        .header-content { font-size: 16px; line-height: 1.5; margin: 0 auto; width: fit-content; color: #fff }
        .merchant-logo { height: 39px; width: 39px; padding: 7px; background-color: #fff; display: inline-block; margin-right: 5px; vertical-align: middle; }
        
        .title-card { background-color: #fff; text-align: center; padding: 12px; width: 100%; box-sizing: border-box; }
        .title-card-top { margin-top: 16px; }
        .success-icon { height: 32px; width: 32px; margin: 0 auto; }
        
        .amount-display { color: #0d2366; font-weight: 900; font-size: 24px; line-height: 1.5; }
        .amount-subunit { font-size: 86%; }
        .subtitle { color: #7b8199; font-weight: 200; font-size: 16px; line-height: 1.5; }
        
        .info-row { font-size: 14px; line-height: 1.5; width: 100%; padding-left: 2%; margin-bottom: 20px; clear: both; }
        .info-label { width: 45%; display: inline-block; color: #7b8199; vertical-align: top; font-weight: 700; }
        .info-value { display: inline-block; float: right; color: #515978; width: 50%; text-align: right; }
        
        .footer { font-size: 12px; padding: 10px 12px; text-align: center; color: #7b8199; }
        .footer-line { margin: 16px 0; }
        .flag-icon { height: 10px; width: 10px; display: inline-block; margin-right: 4px; }
        .org-logo { height: 18px; width: auto; }
        
        a.link { text-decoration: none; color: #528ff0; }
    </style>
</head>

<body>
    <div class="background-split"></div>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="merchant-logo">
                    <img src="{{brand_logo}}" alt="Merchant Logo">
                </div>
                <span>Merchant Name</span>
            </div>
            <div class="title-card title-card-top">
                <div class="success-icon">
                    <img src="https://cdn.razorpay.com/static/assets/email/check-success-green.png" alt="Success">
                </div>
            </div>
        </div>
        
        <div class="title-card">
            <div class="amount-display">
                <span>₹</span>
                <span>12</span>
                <span class="amount-subunit">.34</span>
            </div>
            <div class="subtitle">
                Paid Successfully
            </div>
        </div>
        
        <div class="card">
            <div class="info-row">
                <div class="info-label">Payment Id:</div>
                <div class="info-value">pay_ABC123456789</div>
            </div>
            <div class="info-row">
                <div class="info-label">Method</div>
                <div class="info-value">Credit Card</div>
            </div>
            <div class="info-row">
                <div class="info-label">&nbsp;</div>
                <div class="info-value">VISA ****1234</div>
            </div>
            <div class="info-row">
                <div class="info-label">Paid On</div>
                <div class="info-value">May 16, 2025, 12:34 PM</div>
            </div>
        </div>
        
        <div class="card">
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">customer@example.com</div>
            </div>
            <div class="info-row">
                <div class="info-label">Mobile Number</div>
                <div class="info-value">+1 (555) 123-4567</div>
            </div>
        </div>
        
        <div class="card">
            <div style="font-size: 14px; text-align: center; color: #515978;">
                For any order related queries please reach out to
                <a class="link" href="#">Merchant Name</a>
                at&nbsp;<a class="link" href="mailto:support@example.com">support@example.com</a> or on +1 (555) 987-6543
            </div>
        </div>
        
        <div class="footer">
            <div class="footer-line">
                Please report this payment if you find it to be suspicious<br>
                or fraudulent&nbsp;
                <div class="flag-icon">
                    <img src="https://cdn.razorpay.com/static/assets/email/flag_outline.png" alt="Flag">
                </div>
                <a class="link" href="#">Report Payment</a>
            </div>
            <div class="footer-line" style="display: grid;">
                <div style="display: inline-block;">
                    <img class="org-logo" src="https://cdn.razorpay.com/logo-small.png" alt="Organization">
                </div>
                <div style="display: inline-block; vertical-align: middle;">
                    Powered By Razorpay
                </div>
            </div>
        </div>
    </div>
</body>
</html>`;
