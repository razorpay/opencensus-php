import { rest } from 'msw';
import { server } from 'test-utils';

export const fetchPaymentPageEntity = () => {
  return rest.get('*/merchant/api/:mode/payment_pages/:id/details', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id: 'pl_LDzywAOTf36CYX',
          amount: null,
          currency: 'INR',
          currency_symbol: '₹',
          expire_by: null,
          times_payable: null,
          times_paid: 0,
          total_amount_paid: 0,
          status: 'active',
          status_reason: null,
          short_url: 'https://rzp.io/l/DxefweQ1P',
          user_id: 'IPpdzCXwlY7SFz',
          user: {
            id: 'IPpdzCXwlY7SFz',
            name: 'Danish Kamal',
            email: 'danish.kamal@razorpay.com',
            contact_mobile: '9670220092',
            contact_mobile_verified: true,
            email_verified: true,
            second_factor_auth: false,
            second_factor_auth_enforced: false,
            second_factor_auth_setup: true,
            org_enforced_second_factor_auth: false,
            restricted: false,
            confirmed: true,
            account_locked: false,
            created_at: 1637842334,
            signup_via_email: 1,
          },
          receipt: {
            enable_receipt: '1',
            selected_udf_field: '',
            enable_custom_serial_number: '0',
            enable_80g_details: '0',
          },
          title: 'Please Pay',
          description: null,
          notes: [],
          support_contact: null,
          support_email: null,
          terms: null,
          type: 'payment',
          payment_page_items: [
            {
              id: 'ppi_LDzywF3NXRQrtU',
              entity: 'payment_page_item',
              payment_link_id: 'pl_LDzywAOTf36CYX',
              item: {
                id: 'item_LDzywFIYWr9U4s',
                active: true,
                name: 'Amount',
                description: null,
                amount: 5000,
                unit_amount: 5000,
                currency: 'INR',
                type: 'payment_page',
                unit: null,
                tax_inclusive: false,
                hsn_code: null,
                sac_code: null,
                tax_rate: null,
                tax_id: null,
                tax_group_id: null,
                created_at: 1675869918,
              },
              mandatory: true,
              image_url: null,
              stock: null,
              quantity_sold: 0,
              total_amount_paid: 0,
              min_purchase: null,
              max_purchase: null,
              min_amount: null,
              max_amount: null,
              settings: {
                position: '0',
              },
              plan_id: null,
              product_config: null,
            },
          ],
          created_at: 1675869918,
          updated_at: 1675869918,
          slug: 'DxefweQ1P',
          captured_payments_count: 0,
          settings: {
            payment_button_label: '',
            theme: 'light',
            allow_social_share: '0',
            payment_success_redirect_url: '',
            udf_schema:
              '[{"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":0}},{"name":"phone","title":"Phone","required":true,"type":"number","pattern":"phone","minLength":"8","options":[],"settings":{"position":1}}]',
            checkout_options: {
              email: 'email',
              phone: 'phone',
            },
            payment_button_text: 'Pay Now',
            payment_button_theme: 'rzp-dark-standard',
            payment_button_template_type: 'quick-pay',
            version: 'V2',
            enable_receipt: '1',
            selected_udf_field: '',
            enable_custom_serial_number: '0',
            enable_80g_details: '0',
          },
        },
      }),
      ctx.delay(10),
    );
  });
};

export const fetchPaymentsListForPaymentPage = () => {
  return rest.get('*/merchant/api/:mode/payments', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          entity: 'collection',
          count: 0,
          has_more: false,
          items: [],
        },
      }),
      ctx.delay(10),
    );
  });
};

export const fetchPaymentButtonPageInit = () => {
  return server.use(fetchPaymentPageEntity(), fetchPaymentsListForPaymentPage());
};
