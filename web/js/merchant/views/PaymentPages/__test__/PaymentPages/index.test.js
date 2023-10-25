import { renderApp } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/PaymentPages';
import { screen } from 'test-utils';
import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';
import { getBatchQuickGuideData } from 'merchant/views/PaymentPages/QuickGuide/data';

describe('PaymentPagesContainer', () => {
  beforeAll(() => {
    window.rzpQ = {
      paymentPages: () => ({
        interaction: jest.fn(),
      }),
      component: jest.fn(),
    };
  });

  test('should show batch payment page quick guide banner', () => {
    renderApp({
      initialEntries: BATCH_PAYMENT_PAGES_BASE_URL,
    });

    const paymentPageTitle = screen.getByText(getBatchQuickGuideData.paymentPage.title);
    const paymentPageContent = screen.getByText(getBatchQuickGuideData.paymentPage.content);
    const uploadFileTitle = screen.getByText(getBatchQuickGuideData.uploadFile.title);
    const uploadFileContent = screen.getByText(getBatchQuickGuideData.uploadFile.content);
    const receivePaymentsTitle = screen.getByText(getBatchQuickGuideData.receivePayments.title);
    const receivePaymentsContent = screen.getByText(getBatchQuickGuideData.receivePayments.content);

    expect(paymentPageTitle).toBeInTheDocument();
    expect(paymentPageContent).toBeInTheDocument();
    expect(uploadFileTitle).toBeInTheDocument();
    expect(uploadFileContent).toBeInTheDocument();
    expect(receivePaymentsTitle).toBeInTheDocument();
    expect(receivePaymentsContent).toBeInTheDocument();
  });
});
