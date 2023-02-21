import {
  screen,
  server,
  within,
  render,
  waitForLoadingToFinish,
  userEvent,
  waitFor,
} from 'test-utils';
import {
  fetchRLDetails,
  cancelRL,
  cancelRLError,
  sendEmail,
  sendSms,
  sendEmailError,
  sendSmsError,
  downloadSignedNachForm,
  downloadSignedNachFormWithError,
} from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/RegistrationLinks/Details';
import App from 'merchant/views/Subscriptions/RegistrationLinks/Details';
import analytics from 'merchant/views/Subscriptions/analytics';

describe('Test Registration Link Details - All Methods except Nach', () => {
  let analyticsSpy;

  beforeEach(async () => {
    window.rzpQ = {
      interaction: jest.fn(),
    };
    server.use(fetchRLDetails());
    render(<App />, { showModal: true });
    await waitForLoadingToFinish();
    analyticsSpy = jest.spyOn(analytics, 'track');
  });

  afterEach(() => {
    analyticsSpy.mockClear();
  });

  test('Should render all the registration link detail fields', () => {
    [
      'inv_l5envkoopcbamw',
      'status',
      '^amount$',
      'currency',
      '^link$',
      '^receipt$',
      'description',
      'method',
      'customer details',
      'created at',
      'expiry',
      'notes',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    ['cancel link', 'resend link', 'show/hide'].forEach((fieldLabel) => {
      expect(screen.getByRole('button', { name: new RegExp(fieldLabel, 'i') })).toBeInTheDocument();
    });
  });

  test('Should render all the registration link detail field values', () => {
    [
      'issued',
      '^0$',
      '^inr$',
      'https://rzp.io/i/llbmkjs',
      'receipt no.1222',
      'test registration link',
      'january 17, 2023, 12:04 pm',
      '19 jan 2038',
      // Payment Method Details
      'emandate',
      'auth type: netbanking',
      // Customer Details
      'suhas more',
      'dsuhas4u@gmail.com',
      '9689649696',
      'cust_idb4n9k5aondon',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    const maxAmountView = screen.getByText(/max amount:/i);
    expect(within(maxAmountView).getByText(/^50$/i)).toBeInTheDocument();
  });

  test('Should Cancel Registration Link Successfully', async () => {
    const cancelLink = screen.getByRole('button', {
      name: /cancel link/i,
    });
    await userEvent.click(cancelLink);
    expect(analyticsSpy).toHaveBeenCalledTimes(1);
    expect(analyticsSpy).toHaveBeenCalledWith('registration_link.cancel.initiate', undefined);
    const cancelPostiveBtn = screen.getByRole('button', {
      name: /yes, cancel/i,
    });
    server.use(cancelRL());
    await userEvent.click(cancelPostiveBtn);

    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('registration_link.cancel.success', undefined);
    });
  });

  test('Should fail the Cancel Registration Link with error', async () => {
    const cancelLink = screen.getByRole('button', {
      name: /cancel link/i,
    });
    await userEvent.click(cancelLink);
    expect(analyticsSpy).toHaveBeenCalledWith('registration_link.cancel.initiate', undefined);
    const cancelPositiveBtn = screen.getByRole('button', {
      name: /yes, cancel/i,
    });
    server.use(cancelRLError());
    await userEvent.click(cancelPositiveBtn);

    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('registration_link.cancel.fail', {
        response: ['Authentication failed', 'Status Code: 400'],
      });
    });
  });

  test('Should Resend Registation Link Successfully', async () => {
    const reSendLinkBtn = screen.getByRole('button', {
      name: /resend link/i,
    });
    await userEvent.click(reSendLinkBtn);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('registration_link.resend.initiate', undefined);
    });

    const sendLinkBtn = screen.getByRole('button', {
      name: /^send link$/i,
    });
    server.use(sendEmail());
    server.use(sendSms());
    await userEvent.click(sendLinkBtn);

    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('registration_link.resend.success', undefined);
    });
  });

  test('Should fail the Resend Registation Link with error', async () => {
    const reSendLinkBtn = screen.getByRole('button', {
      name: /resend link/i,
    });
    await userEvent.click(reSendLinkBtn);
    const sendLinkBtn = screen.getByRole('button', {
      name: /^send link$/i,
    });
    server.use(sendEmailError());
    server.use(sendSmsError());
    await userEvent.click(sendLinkBtn);

    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('registration_link.resend.fail', undefined);
    });
  });

  test('Should Show/Hide Notes on click', async () => {
    const showBtn = screen.getByRole('button', {
      name: /show\/hide/i,
    });
    const note1 = 'note_key_2';
    const note2 = 'Tea. Earl grey. Decaf.';

    await userEvent.click(showBtn);
    expect(screen.getByText(note1)).toBeInTheDocument();
    expect(screen.getByText(note2)).toBeInTheDocument();

    await userEvent.click(showBtn);
    expect(screen.queryByText(note1)).not.toBeInTheDocument();
    expect(screen.queryByText(note2)).not.toBeInTheDocument();
  });
});

describe('Test Registration Link Details - Only Nach', () => {
  let analyticsSpy;

  beforeEach(async () => {
    window.rzpQ = {
      interaction: jest.fn(),
    };
    server.use(fetchRLDetails(true));
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
      'registration_link.nach.download_signed_nach.initiate',
      undefined,
    );
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith(
        'registration_link.nach.download_signed_nach.success',
        undefined,
      );
    });
  });

  test('Should render Nach Form Fields and fail the download signed form with error', async () => {
    const viewSignedForm = screen.getByRole('button', { name: /View Signed NACH Form/i });
    server.use(downloadSignedNachFormWithError());
    await userEvent.click(viewSignedForm);
    expect(analyticsSpy).toHaveBeenCalledWith(
      'registration_link.nach.download_signed_nach.initiate',
      undefined,
    );
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith(
        'registration_link.nach.download_signed_nach.error',
        { response: 'Signed Form is not available' },
      );
    });
  });
});
