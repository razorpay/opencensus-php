import { rest } from 'msw';
import { server } from 'test-utils';

import { timelineResponse } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/fixtures/PaymentDetailsTimeline';
import { submerchantPaymentAppDetails } from './fixtures/PaymentDetails';

export const mockPaymentIdDetails = ({ error }) => {
  return server.use(
    rest.get('*/merchant/api/:mode/payments/:id', (req, res, ctx) => {
      if (error) {
        return res(ctx.errors([error]), ctx.delay(50));
      }

      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            id: 'pay_MNllbwctd86eTO',
            entity: 'payment',
            amount: 8000,
            currency: 'INR',
            base_amount: 8000,
            status: 'captured',
            order_id: null,
            invoice_id: null,
            international: false,
            method: 'upi',
            amount_refunded: 0,
            amount_transferred: 0,
            refund_status: null,
            captured: true,
            description: 'QRv2 Payment',
            card_id: null,
            card: null,
            bank: null,
            wallet: null,
            vpa: 'random@icici',
            email: null,
            contact: null,
            notes: [],
            fee: 0,
            tax: 0,
            error_code: null,
            error_description: null,
            error_source: null,
            error_step: null,
            error_reason: null,
            acquirer_data: {
              rrn: '93241231420347',
            },
            emi_plan: null,
            disputes: {
              entity: 'collection',
              count: 0,
              items: [],
            },
            created_at: 1691540348,
            fee_bearer: 'platform',
            transaction: {
              id: 'txn_MNlle0zA2PVjll',
              entity: 'transaction',
              entity_id: 'pay_MNllbwctd86eTO',
              type: 'payment',
              debit: 0,
              credit: 0,
              amount: 8000,
              currency: 'INR',
              fee: 0,
              tax: 0,
              on_hold: false,
              settled: false,
              created_at: 1691540348,
              settled_at: 1691951400,
              settlement_id: null,
              posted_at: null,
              credit_type: 'default',
              settlement: null,
            },
            upi: {
              payer_account_type: 'wallet',
              vpa: 'random@icici',
            },
            instant_refund_support: false,
            gateway_refund_support: true,
            direct_settlement_refund: true,
          },
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const mockPaymentIdRefunds = () => {
  return server.use(
    rest.get('*/merchant/api/:mode/payments/:id/refunds', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            count: 1,
            entity: 'collection',
            items: [
              {
                acquirer_data: {
                  rrn: null,
                },
                amount: 1100,
                batch_id: null,
                created_at: 1691836338,
                currency: 'INR',
                entity: 'refund',
                id: 'rfnd_MP7ohriq3n09Pn',
                notes: {
                  comment: '',
                },
                payment_id: 'pay_MNllbwctd86eTO',
                receipt: null,
                speed: 'normal',
                speed_processed: 'normal',
                speed_requested: 'normal',
                status: 'processed',
              },
            ],
          },
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const mockRefundIdDetails = ({ error }) => {
  return server.use(
    rest.get('*/merchant/api/:mode/refunds/:id', (req, res, ctx) => {
      if (error) {
        return res(ctx.errors([error]), ctx.delay(50));
      }
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            acquirer_data: {
              rrn: null,
            },
            amount: 1100,
            batch_id: null,
            created_at: 1691836338,
            currency: 'INR',
            entity: 'refund',
            id: 'rfnd_MP7ohriq3n09Pn',
            notes: {
              comment: '',
            },
            payment_id: 'pay_MNllbwctd86eTO',
            processed_at: 1691836338,
            receipt: null,
            speed_processed: 'normal',
            speed_requested: 'normal',
            status: 'processed',
          },
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const mockPaymentIdTimelineDetails = (status) => {
  return server.use(
    rest.get('*/merchant/api/:mode/merchant/payment/:id/timeline', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: timelineResponse[status],
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const mockBankTransferDetails = () => {
  return server.use(
    rest.get('*/merchant/api/:mode/:id/bank_transfer', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            id: 'bt_MRWo0DySn3Mcup',
            entity: 'bank_transfer',
            payment_id: 'pay_MRWo0NjwYQ2yMy',
            mode: 'IFT',
            bank_reference: 'CMS480097490',
            amount: 343946,
            payer_bank_account: {
              id: 'ba_MRWo1CIyvdC6eB',
              entity: 'bank_account',
              ifsc: 'ICIC0000104',
              bank_name: 'ICICI Bank',
              name: 'CREDIT CARD OPERATIONS',
              notes: [],
              account_number: '010405000010',
            },
            virtual_account_id: 'va_La2OoAmcN48Bq0',
            virtual_account: {
              id: 'va_La2OoAmcN48Bq0',
              name: 'test',
              entity: 'virtual_account',
              status: 'closed',
              description: null,
              amount_expected: null,
              notes: [],
              amount_paid: 2309946,
              customer_id: null,
              receivers: [
                {
                  id: 'ba_La2OqbiyYm0it2',
                  entity: 'bank_account',
                  ifsc: 'RATN0VAAPIS',
                  bank_name: 'RBL Bank',
                  name: 'test',
                  notes: [],
                  account_number: '2223195654343334',
                },
                {
                  id: 'vpa_La2Oqk9el5J2VY',
                  entity: 'vpa',
                  username: 'rzr.passport761372321037',
                  handle: 'icici',
                  address: 'rzr.passport761372321037@icici',
                },
              ],
              close_by: null,
              closed_at: 1680681960,
              created_at: 1680681916,
            },
          },
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const mockfetchSettlementConfig = () => {
  return server.use(
    rest.post('*/merchant/api/:mode/settlements/dashboard/merchant_config/get', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            config: {
              active: true,
              country_code: 'IN',
              features: {
                block: {
                  reason: '',
                  status: false,
                },
                global_hold_config: {
                  reason: '',
                  status: false,
                },
                hold: {
                  reason: '',
                  status: false,
                },
                source_config: {
                  commission: {
                    enabled: false,
                    reason: '',
                    status: false,
                  },
                },
              },
              initiate_types: {
                default: {
                  enable: false,
                },
                delayed: {
                  enable: false,
                  schedule_id: '',
                },
              },
              merchant_email: 'siddharth.arora+1@razorpay.com',
              org_id: '100000razorpay',
              pg_ledger_reverse_shadow_enabled: false,
              schedules: {
                adjustment: {
                  default: 'Instant',
                },
                commission: {
                  default: 'Instant',
                },
                credit_repayment: {
                  default: 'Instant',
                },
                fund_account_validation: {
                  default: 'Instant',
                },
                payment: {
                  'domestic:default': 'T+0 9AM, 5PM',
                  'international:default': 'T+0 9AM, 5PM',
                },
                payout: {
                  default: 'Instant',
                },
                refund: {
                  default: 'Instant',
                },
                reversal: {
                  default: 'Instant',
                },
                'settlement.ondemand': {
                  default: 'Instant',
                },
                settlement_transfer: {
                  default: 'T+0 4PM',
                },
                transfer: {
                  default: 'Instant',
                },
              },
              settle_to_org: false,
              types: {
                aggregate: {
                  enable: false,
                  settle_to: '',
                },
                default: {
                  enable: true,
                },
                transaction_level: {
                  enable: false,
                },
              },
            },
          },
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const mockfetchSchedule = () => {
  return server.use(
    rest.get('*/merchant/api/:mode/schedule_tasks/settlement', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: [
            {
              method: null,
              type: 'settlement',
              name: 'Basic T7',
              period: 'daily',
              interval: 1,
              anchor: null,
              hour: [0],
              delay: 7,
              international: 1,
              is_early_settlement_schedule: false,
            },
            {
              method: null,
              type: 'settlement',
              name: 'Hourly T+1 hour',
              period: 'hourly',
              interval: 1,
              anchor: null,
              hour: [0],
              delay: 0,
              international: 0,
              is_early_settlement_schedule: false,
            },
            {
              method: null,
              type: 'settlement',
              name: 'Basic T+0',
              period: 'hourly',
              interval: 0,
              anchor: null,
              hour: [9, 17],
              delay: 0,
              international: 0,
              is_early_settlement_schedule: true,
            },
          ],
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const mockfetchHolidayList = () => {
  return server.use(
    rest.get('*/merchant/api/:mode/settlement/holidays', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            2023: [
              {
                date: '26/01/2023',
                description: 'Republic Day',
              },
              {
                date: '18/02/2023',
                description: 'Mahashivratri (Maha Vad-14)/Sivarathri',
              },
              {
                date: '07/03/2023',
                description: 'Holi/Holi (Second Day)/Holika Dahan/Dhulandi/Dol Jatra',
              },
              {
                date: '22/03/2023',
                description: 'Gudi Padwa/Ugadi Festival/Bihar Divas/Sajibu Nongmapanba (Cheiraoba)',
              },
              {
                date: '30/03/2023',
                description: 'Shree Ram Navami (Chaite Dashain)',
              },
              {
                date: '01/04/2023',
                description: 'Annual closing of banks',
              },
              {
                date: '04/04/2023',
                description: 'Mahavir Jayanti',
              },
              {
                date: '07/04/2023',
                description: 'Good Friday',
              },
              {
                date: '14/04/2023',
                description: 'Dr. Babasaheb Ambedkar Jayanti/Bohag Bihu/Cheiraoba',
              },
              {
                date: '22/04/2023',
                description: 'Ramzan Eid (Eid-Ul-Fitr)',
              },
              {
                date: '01/05/2023',
                description: 'Maharashtra Day/May Day',
              },
              {
                date: '05/05/2023',
                description: 'Buddha Purnima',
              },
              {
                date: '28/06/2023',
                description: 'Bakri Eid (Eid-Ul-Zuha)',
              },
              {
                date: '29/07/2023',
                description: 'Muharram (Tajiya)',
              },
              {
                date: '15/08/2023',
                description: 'Independence Day',
              },
              {
                date: '16/08/2023',
                description: 'Parsi New Year (Shahenshahi)',
              },
              {
                date: '19/09/2023',
                description: 'Ganesh Chaturthi/Samvatsari (Chaturthi Paksha)',
              },
              {
                date: '28/09/2023',
                description:
                  'Eid-E-Milad/Eid-e-Meeladunnabi - (Prophet Mohammad’s Birthday) (Bara Vafat)',
              },
              {
                date: '02/10/2023',
                description: 'Mahatma Gandhi Jayanti',
              },
              {
                date: '24/10/2023',
                description: 'Dussehra/Dusshera (Vijaya Dashmi)/Durga Puja',
              },
              {
                date: '14/11/2023',
                description:
                  'Diwali (Bali Pratipada)/Deepavali/Vikram Samvant New Year Day/Laxmi Puja',
              },
              {
                date: '27/11/2023',
                description: 'Guru Nanak Jayanti/Karthika Purnima',
              },
              {
                date: '25/12/2023',
                description: 'Christmas',
              },
            ],
          },
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const mockApplicationDetails = ({ data = submerchantPaymentAppDetails }) => {
  return server.use(
    rest.get('*/merchant/api/:mode/partner/subm_payment/app_details', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data,
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const mockPaymentTransfers = () => {
  return server.use(
    rest.get('*/merchant/api/:mode/payments/:id/transfers', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            count: 1,
            entity: 'collection',
            items: [
              {
                id: 'trf_JMMPL4TnlzG30O',
              },
            ],
          },
        }),
        ctx.delay(50),
      );
    }),
  );
};

export const mockFetchEncodedPaymentReceipt = (payment_id) => {
  const response = {
    status_code: 200,
    success: true,
    data: {
      receipt_encoded_image: 'iVBORw0KGgoAAAANSUhEUgAAA..',
    },
  };
  return rest.get(`*/ezetap/receipt/${payment_id}`, (_, res, ctx) =>
    res(ctx.status(200), ctx.json(response), ctx.delay(50)),
  );
};
