import { rest } from 'msw';

export const fetchAddWebsiteWorkflowStatusHandler = () =>
  rest.get('*/merchant/api/live/merchant/activation/websites/status', (req, res, ctx) => {
    return res.once(
      ctx.json({
        status_code: 200,
        success: true,
        data: false,
      }),
      ctx.delay(50),
    );
  });

export const fetchMerchantWebsiteDetailsHandler = () =>
  rest.get('*/merchant/api/test/merchant/website/section', (req, res, ctx) => {
    return res.once(
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id: 'K19Gk7hmId4XPY',
          deliverable_type: null,
          shipping_period: '3-5 days',
          refund_request_period: '1-2 days',
          refund_process_period: '9-15 days',
          warranty_period: null,
          merchant_website_details: {
            terms: {
              status: null,
              website: {
                'https://aakashraina.com/': {
                  url: 'https://aakashraina.com/tncc',
                },
              },
              updated_at: 1660115845,
              published_url: null,
              section_status: 2,
            },
            refund: {
              status: 'submitted',
              website: {
                'https://aakashraina.com/': {
                  url: 'https://aakashraina.com/refund',
                },
              },
              updated_at: 1659942852,
              published_url: null,
              section_status: 2,
            },
            privacy: {
              status: 'submitted',
              website: {
                'https://aakashraina.com/': {
                  url: 'https://aakashraina.com/privacy',
                },
              },
              updated_at: 1659606758,
              appstore_url: {
                'https://apps.apple.com/us/genre/ios/id36': {
                  document_id: 'K1A95EUWccNdPF',
                },
              },
              playstore_url: {
                'https://play.google.com/store/apps/details?id=com.turner.b10runner': {
                  document_id: 'K19GjnjvnO98td',
                },
              },
              published_url: null,
              section_status: 2,
            },
            shipping: {
              status: 'submitted',
              website: {
                'https://aakashraina.com/': {
                  url: 'https://aakashraina.com/shipping',
                },
              },
              updated_at: 1659854776,
              appstore_url: {
                'https://apps.apple.com/us/genre/ios/id36': {
                  document_id: 'K1Xb2H9LWqexRE',
                },
              },
              playstore_url: {
                'https://play.google.com/store/apps/details?id=com.turner.b10runner': {
                  document_id: 'K1XatilVdltP0x',
                },
              },
              published_url: null,
              section_status: 2,
            },
            contact_us: {
              status: 'submitted',
              website: null,
              updated_at: 1659614913,
              appstore_url: {
                'https://apps.apple.com/us/genre/ios/id36': {
                  document_id: 'K1X65o1QR3oCsP',
                },
              },
              playstore_url: {
                'https://play.google.com/store/apps/details?id=com.turner.b10runner': {
                  document_id: 'K1X5lLfGzxmMCO',
                },
              },
              published_url: 'https://sme.np.razorpay.in/compliance/K19Gk7hmId4XPY/contact_us',
              section_status: 3,
            },
          },
          additional_data: {
            support_contact_number: '',
            support_email: '',
          },
          status: 'approved',
          isWebsiteSectionsApplicable: true,
          isGracePeriodApplicable: true,
        },
      }),
      ctx.delay(50),
    );
  });
