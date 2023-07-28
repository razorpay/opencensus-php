import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { App } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/PaymentLinksForm';
import { BATCH_TYPE, NOTIFY_MESSAGE } from 'merchant/views/PaymentPages/PaymentPages/constants';

const onChangeMock = jest.fn();

describe('PaymentLinksForm', () => {
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

  const renderApp = (props = {}) => {
    return render(<App onChange={onChangeMock} {...props} />);
  };

  test('paymentLinksForm should be defined', () => {
    expect(App).toBeDefined();
  });

  test('should have two checkboxes "SMS" & "EMAIL" ', async () => {
    renderApp();
    const checkboxes = screen.queryAllByRole('checkbox');
    await userEvent.click(checkboxes[0]);
    await userEvent.click(checkboxes[1]);
    expect(checkboxes.length).toBe(2);
  });

  test('should show batch notify message if batch type is "payment_page" ', () => {
    renderApp({ batchType: BATCH_TYPE });
    expect(screen.getByText(NOTIFY_MESSAGE.BATCH_PAYMENT_PAGE)).toBeInTheDocument();
  });
});
