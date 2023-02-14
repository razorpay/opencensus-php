import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { App } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/SendAllLinks';

const trackSendAllLinksMock = jest.fn();

describe('SendAllLinks', () => {
  const renderApp = (props = {}) => {
    return render(<App trackSendAllLinks={trackSendAllLinksMock} {...props} />);
  };

  test('SendAllLinks should be defined', () => {
    expect(App).toBeDefined();
  });

  test('should have "sms" & "email" fields', () => {
    renderApp();
    expect(screen.getByText('Send Email')).toBeInTheDocument();
    expect(screen.getByText('Send SMS')).toBeInTheDocument();
  });

  test('should render "Send All" button', async () => {
    renderApp();
    const sendAllBtn = screen.getByRole('button', {
      name: /Yes, Send All/,
    });
    expect(sendAllBtn).toBeInTheDocument();
    await userEvent.click(sendAllBtn);
    expect(trackSendAllLinksMock).toHaveBeenCalled();
  });
});
