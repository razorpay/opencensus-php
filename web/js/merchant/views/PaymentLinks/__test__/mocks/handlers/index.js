import { rest } from 'msw';

export const fetchRemindersHandler = () => {
  return rest.get('*/merchant/api/:mode/reminders/service/merchant_settings', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          count: 1,
          entity: 'merchant_settings',
          items: [
            {
              namespace: 'payment_link_v2',
              merchant_id: 'F1fmiEKKQocbKg',
              active: true,
              max_reminder_count: 3,
              id: 'Jsm4ywlt1caaWx',
              created_at: 1657698730,
              updated_at: 1657698730,
            },
          ],
        },
      }),
      ctx.delay(10),
    );
  });
};

export const fetchRemindersMerchantConfigHandler = () => {
  return rest.get('*/merchant/api/:mode/reminders/service/merchant_config', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          count: 4,
          entity: 'merchant_config',
          items: [
            {
              merchant_id: 'F1fmiEKKQocbKg',
              config_id: 'Fx2Flf08Ne3or2',
              channels: ['email', 'sms'],
              reminder_config: {
                namespace: 'payment_link_v2',
                title: '2 days before expiry date',
                config_template: {
                  attr_key: 'expire_by',
                  interval: 0,
                  interval_unit: 'hour',
                  offset: -48,
                  offset_unit: 'hour',
                  total_count: 1,
                },
                id: 'Fx2Flf08Ne3or2',
                created_at: 1604480703,
                updated_at: 1604480703,
              },
              id: 'KO3fUhg7zkpxLI',
              created_at: 1664529215,
              updated_at: 1665567222,
            },
            {
              merchant_id: 'F1fmiEKKQocbKg',
              config_id: 'En5azGq0jDMwhx',
              channels: ['email', 'sms'],
              reminder_config: {
                namespace: 'payment_link_v2',
                title: '1 day before expiry date',
                config_template: {
                  attr_key: 'expire_by',
                  interval: 1,
                  offset: -24,
                  total_count: 5,
                },
                id: 'En5azGq0jDMwhx',
                created_at: 1588771985,
                updated_at: 1588771985,
              },
              id: 'KMUaqaIUbUUoIe',
              created_at: 1664187354,
              updated_at: 1665567222,
            },
            {
              merchant_id: 'F1fmiEKKQocbKg',
              config_id: 'EqEnhnmPKcWSlA',
              channels: ['email', 'sms'],
              reminder_config: {
                namespace: 'payment_link_v2',
                title: '1 day after created at',
                config_template: {
                  attr_key: 'created_at',
                  interval: 1,
                  offset: 24,
                  total_count: 5,
                },
                id: 'EqEnhnmPKcWSlA',
                created_at: 1589459423,
                updated_at: 1589459423,
              },
              id: 'KGubpHalbCxRwK',
              created_at: 1662968931,
              updated_at: 1665567222,
            },
            {
              merchant_id: 'F1fmiEKKQocbKg',
              config_id: 'Fx25ec6qfp1N8h',
              channels: ['email', 'sms'],
              reminder_config: {
                namespace: 'payment_link_v2',
                title: '2 days after created at',
                config_template: {
                  attr_key: 'created_at',
                  interval: 0,
                  interval_unit: 'hour',
                  offset: 48,
                  offset_unit: 'hour',
                  total_count: 1,
                },
                id: 'Fx25ec6qfp1N8h',
                created_at: 1604480128,
                updated_at: 1604480128,
              },
              id: 'KO3fUiLKAC8tLz',
              created_at: 1664529215,
              updated_at: 1665567222,
            },
          ],
        },
      }),
      ctx.delay(10),
    );
  });
};

export const createPaymentLinkV2 = () => {
  return rest.get('*/merchant/api/:mode/payment_links', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          accept_partial: false,
          amount: 200,
          amount_paid: 0,
          cancelled_at: 0,
          created_at: 1673601761,
          currency: 'INR',
          customer: [],
          description: '',
          expire_by: 0,
          expired_at: 0,
          first_min_partial_amount: 0,
          id: 'plink_L3bul1lreRNeu7',
          notes: null,
          notify: {
            email: false,
            sms: false,
            whatsapp: false,
          },
          payments: null,
          reference_id: '',
          reminder_enable: false,
          reminders: [],
          short_url: 'https://rzp.io/i/23Te6BQ53',
          status: 'created',
          updated_at: 1673601761,
          upi_link: false,
          user_id: 'F1fmi7bRy3m3I0',
        },
      }),
      ctx.delay(10),
    );
  });
};

export const fetchPaymentLinkDetailsV2 = () => {
  return rest.get('*/merchant/api/:mode/payment_links/*', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          accept_partial: false,
          amount: 200,
          amount_paid: 0,
          cancelled_at: 0,
          created_at: 1673601761,
          currency: 'INR',
          customer: [],
          description: '',
          expire_by: 0,
          expired_at: 0,
          first_min_partial_amount: 0,
          id: 'plink_L3bul1lreRNeu7',
          notes: null,
          notify: {
            email: false,
            sms: false,
            whatsapp: false,
          },
          payments: null,
          reference_id: '',
          reminder_enable: false,
          reminders: [],
          short_url: 'https://rzp.io/i/23Te6BQ53',
          status: 'created',
          updated_at: 1673601761,
          upi_link: false,
          user_id: 'F1fmi7bRy3m3I0',
        },
      }),
      ctx.delay(10),
    );
  });
};
