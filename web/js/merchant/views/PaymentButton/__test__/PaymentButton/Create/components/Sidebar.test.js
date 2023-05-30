import React from 'react';
import Sidebar from 'merchant/views/PaymentButton/PaymentButton/Create/components/SideBar';
import { render, screen, userEvent } from 'test-utils';

const defaultProps = {
  paymentButtonEntity: {
    settings: {
      payment_button_template_type: 'quickPay',
      payment_button_text: 'Pay Now',
    },
    title: 'Test Button',
  },
  amountFields: [{ label: 'Amount', name: 'amount' }],
  udfFields: [{ label: 'Email', name: 'email' }],
  stepsProgress: {
    isButtonDetailsReviewed: true,
    isAmountDetailsReviewed: true,
    isCustomerDetailsReviewed: true,
  },
  isSuccessViewOpened: false,
  isSuccessViewOpenedForExistingId: false,
};

describe('SideBar - Unit test', () => {
  beforeAll(() => {
    window.rzpQ = {
      paymentButtons: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  const renderApp = (props) => render(<Sidebar {...defaultProps} {...props} />);

  test('renders the component', () => {
    renderApp();
    expect(screen.getByText('Creation Progress')).toBeInTheDocument();
    expect(screen.getByText('Customer Details')).toBeInTheDocument();
  });

  test('disables the "Review and Create" step if not all previous steps are done', () => {
    const newProps = {
      ...defaultProps,
      stepsProgress: {
        isButtonDetailsReviewed: true,
        isAmountDetailsReviewed: false,
        isCustomerDetailsReviewed: true,
      },
    };
    renderApp(newProps);
    expect(screen.getByText('Review and Create')).toBeInTheDocument();
  });

  test('calls the onClick function when a step is clicked', async () => {
    const onClick = jest.fn();
    renderApp({ onClick });
    const buttonDetailsStep = screen.getByText('Button Details');
    await userEvent.click(buttonDetailsStep);
    expect(buttonDetailsStep).toHaveClass('step-title');
  });
});
