import { screen, userEvent, within, server, waitFor } from 'test-utils';
import {
  renderInitialApp,
  renderApp,
  renderAppWithError,
  renderAppWithoutEmandate,
  fetchSettings,
  saveSettings,
  saveSettingsError,
} from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/Settings';
import * as analytics from 'merchant/views/Subscriptions/analytics';

describe('Subscription Settings', () => {
  beforeEach(() => {
    server.use(fetchSettings());
  });

  test('Should render Settings component in loading state', () => {
    expect(renderInitialApp).not.toThrowError();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test('Should render Settings with error', () => {
    renderAppWithError();
    expect(screen.getByText('Unable to fetch settings')).toBeInTheDocument();
  });

  test('Should Contain Take Tour CTA & Documentation Link', async () => {
    renderApp();
    const getTour = screen.getByText(/need help\? take a tour/i);
    const documentationLink = screen.getByRole('link', {
      name: /documentation/i,
    });

    const analyticsTrackMock = jest.spyOn(analytics, 'track');
    await userEvent.click(getTour);
    expect(analyticsTrackMock).toHaveBeenCalledTimes(1);
    expect(analyticsTrackMock).toHaveBeenCalledWith('setting.search.help');
    await userEvent.click(documentationLink);
    expect(analyticsTrackMock).toHaveBeenCalledTimes(2);
    expect(analyticsTrackMock).toHaveBeenCalledWith('setting.search.documentation');
  });

  test('Should render card settings if enabled', () => {
    renderApp();

    [
      '^card$',
      'accept recurring payments via debit & credit cards for your subscriptions in any of our',
      'only limited cards are supported due to new payment regulations by rbi.',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    const acceptPaymentView = screen.getAllByText(/accept payments upto/i)[0];
    expect(within(acceptPaymentView).getByText(/2,00,000/i)).toBeInTheDocument();

    const maxPaymentView = screen.getAllByText(/payments above/i)[0];
    expect(within(maxPaymentView).getByText(/15,000/i)).toBeInTheDocument();
    expect(
      within(maxPaymentView).getByText(/will ask the customer for otp verification as well\./i),
    ).toBeInTheDocument();

    expect(
      screen.getByRole('link', {
        name: /view supported cards/i,
      }),
    ).toBeInTheDocument();
  });

  test('Should render UPI settings if enabled', () => {
    renderApp();

    [
      '^upi$',
      'accept recurring payments via upi apps like phonepe, paytm & bhim for your subscriptions. only supports indian currency.',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    const acceptPaymentView = screen.getAllByText(/accept payments upto/i)[1];
    expect(within(acceptPaymentView).getByText(/1,00,000/i)).toBeInTheDocument();

    const bfscView = screen.getByText(/\(for bfsi:/i);
    expect(within(bfscView).getByText(/2,00,000/i)).toBeInTheDocument();

    const maxPaymentView = screen.getAllByText(/payments above/i)[1];
    expect(within(maxPaymentView).getByText(/5,000/i)).toBeInTheDocument();
    expect(
      within(maxPaymentView).getByText(/will ask the customer for upi pin verification as well\./i),
    ).toBeInTheDocument();
  });

  test('Should render Emandate settings if enabled', () => {
    renderApp();

    [
      '^emandate$',
      'accept recurring payments directly via bank accounts for your subscriptions. only supports indian currency.',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    const view = screen.getByText(/accept payments upto:/i);
    expect(within(view).getByText(/1,00,00,000/i)).toBeInTheDocument();
  });

  test('Should not render Emandate settings if payment method is not enabled for merchant', () => {
    renderAppWithoutEmandate();
    expect(screen.queryByText(/emandate/i)).not.toBeInTheDocument();
  });

  test('Settings - Toggle Card Action: API Success', async () => {
    server.use(saveSettings());
    renderApp();

    const cardBtn = screen.getAllByRole('button')[0];
    await userEvent.click(cardBtn);
    await waitFor(() => {
      expect(screen.getByTestId('Notification--success')).toHaveTextContent(
        'Payment method card disabled successfully',
      );
    });

    await userEvent.click(cardBtn);
    await waitFor(() => {
      expect(screen.getByTestId('Notification--success')).toHaveTextContent(
        'Payment method card enabled successfully',
      );
    });
  });

  test('Settings - Toggle Card Action: API Fail', async () => {
    server.use(saveSettingsError());
    renderApp();
    const cardBtn = screen.getAllByRole('button')[0];
    await userEvent.click(cardBtn);
    await waitFor(() => {
      expect(screen.getByTestId('Notification--error')).toHaveTextContent('Bad Request');
    });
  });
});
