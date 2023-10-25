import { render } from 'test-utils';
import PaymentPagesContainer from 'merchant/views/PaymentPages/index';

const paymentPageDetails = {
  status_code: 200,
  success: true,
  data: {
    entity: 'collection',
    count: 1,
    items: [
      {
        id: 'pl_MklUm63imLMxVk',
        amount: null,
        currency: 'INR',
        currency_symbol: '₹',
        expire_by: null,
        times_payable: null,
        times_paid: 0,
        total_amount_paid: 0,
        status: 'active',
        status_reason: null,
        short_url: 'https://rzp.io/l/wJuQwZAX',
        user_id: 'J1LL6KNhnPcSGD',
        receipt: null,
        title: 'Test CF msg',
        description: null,
        notes: [],
        support_contact: '7905204668',
        support_email: 'nikhilesh.tripathi@razorpay.com',
        terms: null,
        type: 'payment',
        payment_page_items: [
          {
            id: 'ppi_MklUmL1KlRURfl',
            entity: 'payment_page_item',
            payment_link_id: 'pl_MklUm63imLMxVk',
            item: {
              id: 'item_MklUmLPpdp4aeL',
              active: true,
              name: 'Amount',
              description: null,
              amount: 100,
              unit_amount: 100,
              currency: 'INR',
              type: 'payment_page',
              unit: null,
              tax_inclusive: false,
              hsn_code: null,
              sac_code: null,
              tax_rate: null,
              tax_id: null,
              tax_group_id: null,
              created_at: 1696561213,
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
            plan_id: null,
            product_config: null,
          },
        ],
        created_at: 1696561212,
        updated_at: 1696561212,
      },
    ],
  },
};

jest.mock('merchant/views/PaymentPages/PaymentPages/model', () => ({
  fetchPaymentPagesList: () => {
    return Promise.resolve(paymentPageDetails);
  },
}));

export const renderApp = ({ props = {}, initialEntries = '/' } = {}) => {
  return render(<PaymentPagesContainer {...props} />, {
    initialEntries: [initialEntries],
    path: initialEntries,
  });
};
