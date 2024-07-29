import { rest } from 'msw';

export const updateConfig = () => {
  return rest.post('*/merchant/api/:mode/account/config/logo', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id: 'HNi06UzUsLuj8c',
          name: 'est',
          fee_bearer: 'platform',
          transaction_report_email: ['nikhilesh.tripathi@razorpay.com'],
          invoice_label_field: 'business_name',
          auto_capture_late_auth: false,
          brand_color: null,
          handle: null,
          logo_url: null,
          fee_credits_threshold: null,
          amount_credits_threshold: null,
          refund_credits_threshold: null,
          balance_threshold: null,
          display_name: null,
          default_refund_speed: 'normal',
          rect_logo_url: 'https://betacdn.np.razorpay.in/logos/O2oTEMguJmaRvp_original.png',
          preview_image_url: {
            file_id: 'file_O2oTHE8V4KgYqf',
            signed_url:
              'https://rzp-1018-nonprod-test-bucket.s3.ap-south-1.amazonaws.com/preview_1714038900.jpeg',
            name: 'preview_1714038900.jpeg',
            mime: 'image/jpeg',
            extension: 'jpeg',
            display_name: 'preview_1714038900',
            merchant_id: 'HNi06UzUsLuj8c',
            type: 'qr_code_image',
            entity_type: '',
            entity_id: '',
            comments: null,
            size: 378363,
            store: 's3',
            status: 'uploaded',
            metadata: {
              'Content-Disposition': 'attachment; filename=preview_1714038900.jpeg',
            },
            upload_url: null,
            created_at: 1714038901,
          },
        },
      }),
      ctx.delay(50),
    );
  });
};
