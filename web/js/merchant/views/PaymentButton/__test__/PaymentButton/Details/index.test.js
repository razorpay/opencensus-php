import { fetchPaymentButtonPageInit } from 'merchant/views/PaymentButton/__test__/mocks/handlers';
import { App } from 'merchant/views/PaymentButton/__test__/mocks/fixtures/Details';

jest.mock('merchant/views/PaymentPages/PaymentPages/model', () => ({
  fetchPaymentPageEntity: jest.fn(),
  fetchPaymentsListForPaymentPage: jest.fn(),
}));

describe('Payment Button Details - UT', () => {
  fetchPaymentButtonPageInit();
  beforeAll(() => {
    window.rzpQ = {
      paymentButtons: () => ({
        interaction: jest.fn(),
      }),
      component: jest.fn(),
    };
  });

  test('should have payment button details app component defined', () => {
    expect(App).toBeDefined();
  });
});
