import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { App } from 'merchant/views/PaymentButton/__test__/mocks/fixtures/GetCodeModal';

const closeModal = jest.fn();

describe('Payment Button Component GetCodeModal Component', () => {
  const renderApp = () => render(<App closeModal={closeModal} />);

  test('Payment Button App component should be defined', () => {
    expect(App).toBeDefined();
  });

  test('Payment Button GetCodeModal component should render', () => {
    renderApp();
    expect(screen.getByText('Your payment button is ready to go!')).toBeInTheDocument();
    // Starting statement should be there.
    expect(screen.getByText('How to use this code?')).toBeInTheDocument();
    // See documentation link should be there.
    expect(screen.getByText('See documentation')).toBeInTheDocument();
  });

  test('Payment Button GetCodeModal component should have go back to dashboard', async () => {
    renderApp();
    const backtoDashboard = screen.getByText('Back to Dashboard');
    expect(backtoDashboard).toBeInTheDocument();
    await userEvent.click(backtoDashboard);
    expect(closeModal).toHaveBeenCalled();
  });
});
