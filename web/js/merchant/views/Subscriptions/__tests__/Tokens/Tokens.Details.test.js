import { screen, server, render, waitForLoadingToFinish, userEvent, waitFor } from 'test-utils';
import {
  fetchTokenDetails,
  cancelToken,
  cancelTokenError,
  deleteToken,
  deleteTokenError,
  downloadSignedNachForm,
  downloadSignedNachFormWithError,
  resubmitNachForm,
  resubmitNachFormWithError,
} from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/Tokens';
import App from 'merchant/views/Subscriptions/Tokens/Details';
import analytics from 'merchant/views/Subscriptions/analytics';

describe('Test Token Details - All Methods except Nach', () => {
  let analyticsSpy;

  beforeEach(async () => {
    window.rzpQ = {
      interaction: jest.fn(),
    };
    server.use(fetchTokenDetails());
    render(<App />, {
      showModal: true,
    });
    await waitForLoadingToFinish();
    analyticsSpy = jest.spyOn(analytics, 'track');
  });

  afterEach(() => {
    analyticsSpy.mockClear();
  });

  test('Should render all the Token detail fields', () => {
    [
      'token_L7zPW1go48yDUo',
      'status',
      'payment method',
      'created at',
      'expiry',
      'notes',
      'actions',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    ['charge now', 'cancel token', 'delete token'].forEach((fieldLabel) => {
      expect(screen.getByRole('button', { name: new RegExp(fieldLabel, 'i') })).toBeInTheDocument();
    });
  });

  test('Should render all the Token detail field values', () => {
    [
      'confirmed',
      'upi',
      'january 24, 2023, 10:57 am',
      'until cancelled',
      '^testing$',
      'testing@testing.com',
      '1234567890',
      'cust_kzi5cbvshzfxdq',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('Should Charge Token Successfully', async () => {
    const chargeTokenLink = screen.getByRole('button', {
      name: /charge now/i,
    });
    await userEvent.click(chargeTokenLink);
    expect(analyticsSpy).toHaveBeenCalledTimes(1);
    expect(analyticsSpy).toHaveBeenCalledWith('token.update.charge_now', undefined);
  });

  test('Should Cancel Token Successfully', async () => {
    const cancelLink = screen.getByRole('button', {
      name: /cancel token/i,
    });
    await userEvent.click(cancelLink);
    expect(analyticsSpy).toHaveBeenCalledTimes(1);
    expect(analyticsSpy).toHaveBeenCalledWith('token.cancel.initiate', undefined);
    const cancelPostiveBtn = screen.getByRole('button', {
      name: /yes, cancel/i,
    });
    server.use(cancelToken());
    await userEvent.click(cancelPostiveBtn);

    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('token.cancel.confirm', undefined);
    });
  });

  test('Should fail the Cancel Token cancellation', async () => {
    const cancelLink = screen.getByRole('button', {
      name: /cancel token/i,
    });
    await userEvent.click(cancelLink);
    expect(analyticsSpy).toHaveBeenCalledWith('token.cancel.initiate', undefined);
    const cancelPositiveBtn = screen.getByRole('button', {
      name: /yes, cancel/i,
    });
    server.use(cancelTokenError());
    await userEvent.click(cancelPositiveBtn);

    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('token.cancel.fail', {
        response: 'Status Code: 400',
      });
    });
  });

  test('Should Delete Token Successfully', async () => {
    const deleteLink = screen.getByRole('button', {
      name: /delete token/i,
    });
    await userEvent.click(deleteLink);
    expect(analyticsSpy).toHaveBeenCalledTimes(1);
    expect(analyticsSpy).toHaveBeenCalledWith('token.delete.initiate', undefined);
    const deletePostiveBtn = screen.getByRole('button', {
      name: /yes, delete/i,
    });
    server.use(deleteToken());
    await userEvent.click(deletePostiveBtn);

    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('token.delete.confirm', undefined);
    });
  });

  test('Should fail the Delete Token', async () => {
    const deleteLink = screen.getByRole('button', {
      name: /delete token/i,
    });
    await userEvent.click(deleteLink);
    expect(analyticsSpy).toHaveBeenCalledWith('token.delete.initiate', undefined);
    const deletePositiveBtn = screen.getByRole('button', {
      name: /yes, delete/i,
    });
    server.use(deleteTokenError());
    await userEvent.click(deletePositiveBtn);

    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('token.delete.fail', {
        response: 'Status Code: 400',
      });
    });
  });
});

describe('Test Token Details - Only Nach', () => {
  let analyticsSpy;

  beforeEach(async () => {
    window.rzpQ = {
      interaction: jest.fn(),
    };
    server.use(fetchTokenDetails(true));
    render(<App />, { showModal: true });
    await waitForLoadingToFinish();
    analyticsSpy = jest.spyOn(analytics, 'track');
  });

  afterEach(() => {
    analyticsSpy.mockClear();
  });

  test('Should render Nach Form Fields and download form successfully', async () => {
    expect(screen.getByText(/^nach form$/i)).toBeInTheDocument();
    const viewSignedForm = screen.getByRole('button', { name: /View Signed NACH Form/i });
    server.use(downloadSignedNachForm());
    await userEvent.click(viewSignedForm);
    expect(analyticsSpy).toHaveBeenCalledWith(
      'token.nach.download_signed_nach.initiate',
      undefined,
    );
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith(
        'token.nach.download_signed_nach.success',
        undefined,
      );
    });
  });

  test('Should render resubmit nach form', async () => {
    server.use(resubmitNachForm());
    const resubmitBtn = screen.getByText(/^Resubmit$/i);
    await userEvent.click(resubmitBtn);

    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith(
        'token.nach.download_signed_nach.initiate',
        undefined,
      );
      expect(analyticsSpy).toHaveBeenCalledWith(
        'token.nach.download_signed_nach.success',
        undefined,
      );
    });
  });

  test('Should resubmit nach successfully', async () => {
    server.use(resubmitNachForm());
    const resubmitBtn = screen.getByText(/^Resubmit$/i);
    await userEvent.click(resubmitBtn);

    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith(
        'token.nach.download_signed_nach.initiate',
        undefined,
      );
      expect(analyticsSpy).toHaveBeenCalledWith(
        'token.nach.download_signed_nach.success',
        undefined,
      );
    });
  });

  test('Should fail the resubmit nach with error', async () => {
    server.use(resubmitNachFormWithError());
    const resubmitBtn = screen.getByText(/^Resubmit$/i);
    await userEvent.click(resubmitBtn);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('token.nach.download_signed_nach.fail', {
        response: ['error', 'Error during Resubmit'],
      });
    });
  });

  test('Should render Nach Form Fields and fail the download of signed form', async () => {
    const viewSignedForm = screen.getByRole('button', { name: /View Signed NACH Form/i });
    server.use(downloadSignedNachFormWithError());
    await userEvent.click(viewSignedForm);
    expect(analyticsSpy).toHaveBeenCalledWith(
      'token.nach.download_signed_nach.initiate',
      undefined,
    );
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('token.nach.download_signed_nach.error', {
        response: 'Signed Form is not available',
      });
    });
  });
});

describe('test suite for i18n', () => {
  test('Should Contain RM as currency symbol', async () => {
    server.use(fetchTokenDetails(true));
    render(<App loading={false} />, {
      initialState: {
        session: { user: { merchant: { currency: 'MYR' } } },
      },
      showModal: true,
    });
    await waitForLoadingToFinish();
    const chargeTokenLink = screen.getByRole('button', {
      name: /charge now/i,
    });
    expect(chargeTokenLink).toHaveTextContent('RM');
  });
});
