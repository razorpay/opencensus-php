import React from 'react';
import {
  render,
  screen,
  fireEvent,
  server,
  userEvent,
  waitForLoadingToFinish,
  waitFor,
  delay,
} from 'test-utils';

import User from 'merchant/models/User';
import CreateV2 from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/index';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';
import * as apiHelpers from 'merchant/views/PaymentLinks/PaymentLinks/model';
import * as handlers from 'merchant/views/PaymentLinks/__test__/mocks/handlers';
import { showDynamicFields } from 'merchant/views/PaymentLinks/utils';
import { fetchOauthConnectedApplications } from 'merchant/reducers/applications';

jest.mock('merchant/views/PaymentLinks/utils', () => ({
  ...jest.requireActual('merchant/views/PaymentLinks/utils'),
  showDynamicFields: jest.fn(),
}));

const mockedFn = jest.fn();

jest.mock('common/i18', () => ({
  __esModule: true,
  withI18Service: (Component) => (props) =>
    <Component {...props} i18={{ isConfigTagEnabled: mockedFn }} />,
  useI18Service: () => ({
    isConfigTagEnabled: jest.fn(),
  }),
}));

jest.mock('common/splitz', () => ({
  withSplitzService: (Component) => (props) => <Component {...props} splitz={{}} />,
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

jest.mock('merchant/reducers/applications', () => ({
  ...jest.requireActual('merchant/reducers/applications'),
  fetchOauthConnectedApplications: jest.fn(),
}));

const onCloseMock = jest.fn();

const DEFAULT_USER = new User({
  current: 1,
  merchant: {
    country_code: 'IN',
    currency: 'INR',
    product_international: '0000000000',
  },
  merchants: {
    1: {
      country_code: 'IN',
      currency: 'INR',
      product_international: '111',
    },
  },
});

jest.spyOn(track.lj.form, 'create').mockImplementation(() => {});
jest.spyOn(apiHelpers, 'createPaymentLinkV2');

describe('Payment Link Create V2 Unit Test', () => {
  beforeAll(() => {
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
      productOnboarding: () => ({
        success: jest.fn(),
      }),
    };
    fetchOauthConnectedApplications.mockReturnValue({});
  });

  afterEach(() => {
    jest.useRealTimers();
    jest.clearAllMocks();
  });

  const renderApp = (
    props = {},
    initialState = {
      session: {
        user: DEFAULT_USER,
      },
    },
  ) => {
    return render(<CreateV2 {...props} onClose={onCloseMock} />, {
      initialState,
    });
  };

  test('createV2 component should be defined', () => {
    expect(CreateV2).toBeDefined();
  });

  test('should render two options "Standard Payment Link" & "UPI Payment Link" to generate a payment link in v2', () => {
    renderApp();
    expect(screen.getByText('Standard Payment Link')).toBeInTheDocument();
    expect(screen.getByText('UPI Payment Link')).toBeInTheDocument();
  });

  test('should render Standard Payment Link form & Create Payment Link button CTA', async () => {
    renderApp();
    const createNowBtns = screen.getAllByText('Create Now');
    expect(createNowBtns.length).toBe(2);
    await userEvent.click(createNowBtns[0]);
    expect(screen.getByText('Create Payment Link')).toBeInTheDocument();
  });

  test('should render "Create Payment Link" Form with Standard Payment Link Fields', async () => {
    server.use(
      handlers.fetchRemindersHandler(),
      handlers.fetchRemindersMerchantConfigHandler(),
      handlers.createPaymentLinkV2(),
    );
    renderApp();
    const standardPL = screen.getAllByText('Create Now')[0];
    await userEvent.click(standardPL);
    expect(screen.getByText('Standard Payment Link')).toBeInTheDocument();
    //payment link create form should have atleast Amount fields which is a permanent field.
    expect(screen.getByText('Amount')).toBeInTheDocument();
    //payment link create form should have other  sub fields as well.
    expect(screen.getByText('Payment For')).toBeInTheDocument();
    expect(screen.getByText('Notify via SMS')).toBeInTheDocument();
    expect(screen.getByText('Notify via Email')).toBeInTheDocument();
    expect(screen.getByText('Customer Details')).toBeInTheDocument();
    expect(screen.getByText('Send auto reminders')).toBeInTheDocument();
    // create payment link
    const amount = screen.getByPlaceholderText('100.00');
    expect(amount).toBeInTheDocument();
    await userEvent.type(amount, '200');
    const createPLCTA = screen.getByRole('button', {
      name: /Create Payment Link/i,
    });
    await userEvent.click(createPLCTA);
    expect(track.lj.form.create).toHaveBeenCalled();
  });

  test('should have "Link Expiry" & should close on cancel click', async () => {
    renderApp();
    const standardPL = screen.getAllByText('Create Now')[0];
    await userEvent.click(standardPL);
    const dateInput = screen.getByPlaceholderText('DD-MM-YYYY');
    await fireEvent.change(dateInput, { target: { value: '01-01-2022' } });
    const refernceId = screen.getByPlaceholderText('123456');
    await userEvent.tab(refernceId);
    const createPLCTA = screen.getByRole('button', {
      name: /Cancel/i,
    });
    await userEvent.click(createPLCTA);
    expect(onCloseMock).toHaveBeenCalled();
  });

  test(`show open the Standard PL if PaymentLinks.upi_payment_link enabled`, () => {
    server.use(
      handlers.fetchRemindersHandler(),
      handlers.fetchRemindersMerchantConfigHandler(),
      handlers.createPaymentLinkV2(),
    );

    const initialStoreState = {
      session: {
        user: new User({
          current: 1,
          merchant: {
            country_code: 'MY',
            currency: 'MYR',
            product_international: '111',
          },
          merchants: {
            1: {
              country_code: 'MY',
              currency: 'MYR',
              product_international: '111',
            },
          },
        }),
      },
    };
    mockedFn.mockImplementation((path) => path === 'payment_links.upi_payment_link');
    renderApp(null, initialStoreState);

    expect(screen.getByText('Create Payment Link')).toBeInTheDocument();
  });

  test('should have malaysian currency on successful submit', async () => {
    server.use(
      handlers.fetchRemindersHandler(),
      handlers.fetchRemindersMerchantConfigHandler(),
      handlers.createPaymentLinkV2(),
    );

    const initialStoreState = {
      session: {
        user: new User({
          current: 1,
          merchant: {
            country_code: 'MY',
            currency: 'MYR',
            product_international: '111',
          },
          merchants: {
            1: {
              country_code: 'MY',
              currency: 'MYR',
              product_international: '111',
            },
          },
        }),
      },
    };
    mockedFn.mockImplementation((path) => path === 'payment_links.upi_payment_link');
    renderApp(null, initialStoreState);

    await waitForLoadingToFinish();

    // create payment link
    await userEvent.type(screen.getByPlaceholderText('100.00'), '200');
    await userEvent.click(screen.getByRole('button', { name: /Create Payment Link/i }));

    expect(apiHelpers.createPaymentLinkV2).toHaveBeenCalledWith({
      currency: 'MYR',
      amount: 20000,
    });
    expect(track.lj.form.create).toHaveBeenCalled();
    await waitFor(() => expect(onCloseMock).toHaveBeenCalledTimes(1));
  });

  test('should be able to create payment Link successfully', async () => {
    server.use(handlers.createPaymentLinkV2());
    mockedFn.mockImplementation(() => undefined);
    renderApp();

    // Click on standard payment link to open form.
    await userEvent.click(screen.getAllByText('Create Now')[0]);

    // Fill amount field.
    await userEvent.type(screen.getByPlaceholderText('100.00'), '1');
    // Fill payment description.
    await userEvent.type(
      screen.getByPlaceholderText('Payment description'),
      'Test payment description',
    );
    // Fill Customer details.
    await userEvent.type(screen.getByPlaceholderText('john@example.com'), 'test@gmail.com');
    await userEvent.type(screen.getByPlaceholderText('+91 9876543210'), '1234567890');
    // Fill reference id field.
    await userEvent.type(screen.getByPlaceholderText('123456'), '123456');
    // Fill link expiry.
    await userEvent.click(screen.getByText('No Expiry'));
    // Fill notes.
    await userEvent.click(screen.getByText('+ Add New'));
    await userEvent.type(screen.getByPlaceholderText('Title (key)'), 'Test note title');
    await userEvent.type(
      screen.getByPlaceholderText('Description (value)'),
      'Test note description',
    );

    // Purposely delaying to capture the notes field title and description values as debounce is used to set the state, so not getting the updated state immediately.
    await delay(500);

    // Create payment link.
    await userEvent.click(screen.getByRole('button', { name: /Create Payment Link/i }));

    expect(apiHelpers.createPaymentLinkV2).toHaveBeenCalledWith({
      currency: 'INR',
      amount: 100,
      description: 'Test payment description',
      email_notify: '1',
      email: 'test@gmail.com',
      sms_notify: '1',
      contact: '1234567890',
      reference_id: '123456',
      expire_by: null,
      notes: { 'Test note title': 'Test note description' },
    });
    expect(track.lj.form.create).toHaveBeenCalled();

    await waitFor(() =>
      expect(screen.queryByRole('button', { name: /Creating.../i })).not.toBeInTheDocument(),
    );

    expect(screen.getByTestId('Notification--success')).toHaveTextContent(
      'Payment link created successfully. Sending via SMS and Email',
    );
    await waitFor(() => expect(onCloseMock).toHaveBeenCalledTimes(1));
  });

  test.skip('should render "Standard Payment Link" form with dynamic fields and should be able to create payment link', async () => {
    showDynamicFields.mockReturnValue(true);

    server.use(handlers.fetchDynamicFieldsSuccess());

    renderApp();

    // Click on standard payment link to open form.
    await userEvent.click(screen.getAllByText('Create Now')[0]);

    // Fill amount field.
    await userEvent.type(screen.getByPlaceholderText('100.00'), '400');
    // Fill account number field.
    await userEvent.type(screen.getByPlaceholderText('Account Number'), 'test_account_number');
    // Fill reference id field.
    await userEvent.type(screen.getByPlaceholderText('123456'), 'test_reference_number');

    // Create payment link.
    await userEvent.click(screen.getByRole('button', { name: /Create Payment Link/i }));

    expect(apiHelpers.createPaymentLinkV2).toHaveBeenCalledWith({
      currency: 'INR',
      amount: 40000,
      custom_fields: { 'Account Number': 'test_account_number' },
      reference_id: 'test_reference_number',
    });
    expect(track.lj.form.create).toHaveBeenCalled();
  });

  test('should render notification error when fetchPaymentLinkCustomFields promise fails', async () => {
    showDynamicFields.mockReturnValue(true);

    server.use(handlers.fetchDynamicFieldsError());

    renderApp();

    await waitFor(() => {
      expect(screen.getByTestId('Notification--error')).toHaveTextContent(
        'Something went wrong Status Code: 404',
      );
    });
  });
});
