import React from 'react';
import {
  render,
  screen,
  fireEvent,
  server,
  userEvent,
  waitForLoadingToFinish,
  waitFor,
} from 'test-utils';

import User from 'merchant/models/User';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

import * as handlers from 'merchant/views/PaymentLinks/__test__/mocks/handlers';
import CreateV2 from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/index';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';
import * as apiHelpers from 'merchant/views/PaymentLinks/PaymentLinks/model';
import { showDynamicFields } from 'merchant/views/PaymentLinks/utils';

jest.mock('merchant/views/PaymentLinks/utils', () => ({
  ...jest.requireActual('merchant/views/PaymentLinks/utils'),
  showDynamicFields: jest.fn(),
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
    const amount = screen.getByPlaceholderText('0.00');
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

  test(`show open the Standard PL if ${HIDDEN_INTERNATIONAL_FEATURES_TAGS.PAYMENT_LINKS.UPIPaymentLink} enabled`, () => {
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
          tags: [HIDDEN_INTERNATIONAL_FEATURES_TAGS.PAYMENT_LINKS.UPIPaymentLink],
        }),
      },
    };

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
          tags: [HIDDEN_INTERNATIONAL_FEATURES_TAGS.PAYMENT_LINKS.UPIPaymentLink],
        }),
      },
    };

    renderApp(null, initialStoreState);

    await waitForLoadingToFinish();

    // create payment link
    await userEvent.type(screen.getByPlaceholderText('0.00'), '200');
    await userEvent.click(screen.getByRole('button', { name: /Create Payment Link/i }));

    expect(apiHelpers.createPaymentLinkV2).toHaveBeenCalledWith({
      currency: 'MYR',
      amount: 20000,
    });
    expect(track.lj.form.create).toHaveBeenCalled();
  });

  test('should render "Standard Payment Link" form with dynamic fields and should be able to create payment link', async () => {
    showDynamicFields.mockReturnValue(true);

    server.use(handlers.fetchDynamicFieldsSuccess());

    renderApp();

    // Click on standard payment link to open form.
    await userEvent.click(screen.getAllByText('Create Now')[0]);

    await waitForLoadingToFinish();

    // Fill amount field.
    await userEvent.type(screen.getByPlaceholderText('0.00'), '400');
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
