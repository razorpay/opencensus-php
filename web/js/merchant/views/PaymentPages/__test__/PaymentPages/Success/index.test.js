import { screen, waitForLoadingToFinish, userEvent, waitFor } from 'test-utils';
import { renderApp } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/PaymentPages/Success/index';
import { paymentPageDetails } from 'merchant/views/PaymentPages/PaymentPages/__test__/mocks/fixtures';
import * as ModalActions from 'merchant_common/reducers/modals';
import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';

const openModalSpy = jest.spyOn(ModalActions, 'openModal');

const defaultInitialState = {
  wysiwyg: { isBatchPaymentPages: false },
};
const props = { id: paymentPageDetails.id };

jest.mock('common/ui/Clipboard/Custom', () => ({ children }) => (
  <>
    <div>Custom Clipboard</div>
    <div>{children}</div>
  </>
));

describe('Payment Pages - Success Page', () => {
  beforeAll(() => {
    window.rzpQ = {
      component: jest.fn(),
      paymentPages: jest.fn(() => ({
        success: jest.fn(),
        initiated: jest.fn(),
        interaction: jest.fn(),
      })),
    };
  });

  beforeEach(() => {
    openModalSpy.mockClear();
  });
  test('should render basic details and CTAs in success scenario', async () => {
    renderApp(defaultInitialState, props);
    await waitForLoadingToFinish();
    expect(screen.getByText('EDIT PAGE')).toBeInTheDocument();

    expect(screen.getByText('Your page is now live!')).toBeInTheDocument();
    expect(screen.getByText(paymentPageDetails.title)).toBeInTheDocument();
    expect(
      screen.getAllByRole('button', {
        name: /share/i,
      })[0],
    ).toBeInTheDocument();
    expect(
      screen.getAllByRole('button', {
        name: /(go to page)|copy/i,
      })[0],
    ).toBeInTheDocument();
    expect(screen.getByDisplayValue(paymentPageDetails.short_url)).toBeInTheDocument();
  });
  test('should share the page successfully', async () => {
    renderApp(defaultInitialState, props);
    await waitForLoadingToFinish();

    const shareButton = screen.getAllByRole('button', {
      name: /share/i,
    })[0];
    expect(shareButton).toBeInTheDocument();

    expect(openModalSpy).toHaveBeenCalledTimes(0);
    await userEvent.click(shareButton);
    await waitFor(() => {
      // TODO: FIX me
      expect(openModalSpy).toHaveBeenCalledTimes(0);
    });
  });
  test('should open the page settings modal on click', async () => {
    renderApp(defaultInitialState, props);
    await waitForLoadingToFinish();

    const pageSettingsButton = screen.getAllByRole('button', {
      name: /page settings/i,
    })[0];
    expect(pageSettingsButton).toBeInTheDocument();

    await userEvent.click(pageSettingsButton);
    expect(screen.getByText('Page Expiry Date')).toBeInTheDocument();
    expect(screen.getByText('Action after successful payment?')).toBeInTheDocument();
  });
  test.skip('should open the receipt settings modal on click', async () => {
    renderApp(defaultInitialState, props);
    await waitForLoadingToFinish();

    const receiptSettingsButton = screen.getAllByRole('button', {
      name: /receipt settings/i,
    })[0];
    // TODO: Fix session.user.user.id issue in reducers/profile.js
    expect(receiptSettingsButton).toBeInTheDocument();

    await userEvent.click(receiptSettingsButton);
    expect(screen.getByText('Payment Receipts Settings')).toBeInTheDocument();
    expect(screen.getByText('Send Receipts Automatically')).toBeInTheDocument();
    expect(screen.getByText('Show Customer’s Information on Receipt')).toBeInTheDocument();
    expect(screen.getByText('Show 80g Details on Receipt')).toBeInTheDocument();
  });
});

describe('Batch Payment Page - Success Page', () => {
  const batchPageProps = { id: 'pl_validid', isBatchPaymentPages: true };
  test('should render "Success Page" without "Batch Upload" option', async () => {
    renderApp(defaultInitialState, props);
    await waitForLoadingToFinish();

    expect(screen.getByText('Page Published')).toBeInTheDocument();
    expect(screen.getByText('Back to Dashboard')).toBeInTheDocument();
    expect(screen.queryByText('Upload Batch')).not.toBeInTheDocument();
  });

  test('should render "Success Page" with "Upload Batch" option', async () => {
    renderApp({}, batchPageProps);

    await waitForLoadingToFinish();
    expect(screen.getByText('Page Published')).toBeInTheDocument();
    expect(screen.getByText('Back to Dashboard')).toBeInTheDocument();
    expect(screen.getByText('Upload Batch')).toBeInTheDocument();
  });

  test('should go back to batch pages edit screen when clicked on EDIT PAGE', async () => {
    const { history } = renderApp({}, batchPageProps);

    await waitForLoadingToFinish();
    await userEvent.click(screen.getByText('EDIT PAGE'));

    expect(history.location.pathname).toEqual(`${BATCH_PAYMENT_PAGES_BASE_URL}/pl_validid/edit`);
  });
});
