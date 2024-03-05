import { renderApp } from 'self-serve/src/App/Transactions/v2/common/components/__tests__/fixtures/mocks/Ftux';
import { screen } from 'apps/self-serve/src/services/test/test-utils';

describe('Ftux', () => {
  test('should render payments view correctly', () => {
    renderApp({ page: 'payments' });
    const imageElement = screen.getByAltText('payments');
    expect(imageElement).toBeInTheDocument();
    const titleElement = screen.getByText('Start collecting payments');
    expect(titleElement).toBeInTheDocument();
    const subtitleElement = screen.getByText(
      'Use Payment Links, Payment Pages, Payment Gateway, and others to collect payments from your customers',
    );
    expect(subtitleElement).toBeInTheDocument();
    const linkElement = screen.getByRole('link', { name: 'Explore payment products' });
    expect(linkElement).toBeInTheDocument();
    expect(linkElement).toHaveAttribute('href', 'https://razorpay.com/docs/#home-payments');
  });

  test('should render failed payments view correctly', () => {
    renderApp({ page: 'failed payments' });
    const imageElement = screen.getByAltText('failed payments');
    expect(imageElement).toBeInTheDocument();
    const titleElement = screen.getByText('Track your failed payments');
    expect(titleElement).toBeInTheDocument();
    const subtitleElement = screen.getByText(
      'Unsuccessful payments due to customer, bank, or business related errors appear here',
    );
    expect(subtitleElement).toBeInTheDocument();
  });

  test('should render refunds view correctly', () => {
    renderApp({ page: 'refunds' });
    const imageElement = screen.getByAltText('refunds');
    expect(imageElement).toBeInTheDocument();
    const titleElement = screen.getByText('Issue full, partial, or instant refunds');
    expect(titleElement).toBeInTheDocument();
    const subtitleElement = screen.getByText(
      'Refunds to customers get deducted from your current balance and appear here',
    );
    expect(subtitleElement).toBeInTheDocument();
    const linkElement = screen.getByRole('link', { name: 'Refunds guide' });
    expect(linkElement).toBeInTheDocument();
    expect(linkElement).toHaveAttribute('href', 'https://razorpay.com/docs/payments/refunds/');
  });
});
