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
              'https://rzp-1018-nonprod-test-bucket.s3.ap-south-1.amazonaws.com/preview_1714038900.jpeg?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Security-Token=FwoGZXIvYXdzEGsaDHMbLuzvWB9MqIpslCKtAUVmGMLid2WWKKk0V1CGDhSV9FRM83OOOn8Mfsu%2FRsGUyKwETLDf5k9qCM41aFKjrPfwhAYdat2eLY2athf%2FrzrPZo3uUK%2B8UYmdg%2F3upW0CU2XP5Au9pcV6GYpa5gP6ukyjf9%2BU01DE%2BUa%2B8UhEyKlAL%2FGXaDKD1g6Gm6twJ13Kxgwijs6p%2BzLtlq7Ec26yLypDDgA1iwOJJEBG3iBrJ0dqETjMjsUvkHG1VPCMKIzOqLEGMi0YyUQOpTsqoDCQAHnixfZfn0VvTNVyED7c37XezNn%2F4sHjqeLAgiuiLMiHfjk%3D&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=ASIARPN2ZIK2FG5366SR%2F20240425%2Fap-south-1%2Fs3%2Faws4_request&X-Amz-Date=20240425T095501Z&X-Amz-SignedHeaders=host&X-Amz-Expires=900&X-Amz-Signature=768aee893e522fcd954ec7ec3dd99c8e162166fa4d74b58c889392e3a2d5bfb1',
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
