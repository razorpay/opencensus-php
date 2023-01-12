import '@testing-library/jest-dom/extend-expect';
import MerchantDetails from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/MerchantDetails';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';

jest.mock('common/ui/Clipboard/Custom', () => ({ children }) => (
  <>
    <div>Custom Clipboard</div>
    <div>{children}</div>
  </>
));

describe('Merchant Details', () => {
  const renderApp = (props) => render(<MerchantDetails {...props} />);

  test('should render merchant id title', () => {
    renderApp({});
    expect(screen.getByText(/Merchant ID/i)).toBeInTheDocument();
  });

  test('should render merchant id value', () => {
    const merchantId = 'I6Wg2wAr5sY6ca';
    renderApp({
      merchantId,
    });
    expect(screen.getByText(merchantId)).toBeInTheDocument();
  });

  test('should render custom clipboard', () => {
    renderApp({});
    expect(screen.getByText(/Custom Clipboard/i)).toBeInTheDocument();
  });

  test('should render link button', async () => {
    renderApp({});
    const buttonButton = screen.getByRole('button', { name: 'Copy' });
    expect(buttonButton).toBeInTheDocument();
    await userEvent.click(buttonButton);
  });
});
