import React from 'react';
import { render, screen, fireEvent, server, userEvent } from 'test-utils';
import * as handlers from 'merchant/views/PaymentLinks/__test__/mocks/handlers';
import CreateV2 from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/index';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

const onCloseMock = jest.fn();
jest.spyOn(track.lj.form, 'create').mockImplementation(() => {});
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
    track.lj.form.create.mockClear();
  });

  const renderApp = (props = {}) => {
    return render(<CreateV2 {...props} onClose={onCloseMock} />, {
      initialState: {
        session: {
          user: {
            merchant: {
              product_international: '0000000000',
            },
          },
        },
      },
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
});
