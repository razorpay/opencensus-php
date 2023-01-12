import { testBreadCrumb } from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import PaymentMethods from 'merchant/views/AccountAndSettings/PaymentMethods';
import { render, screen } from 'test-utils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

jest.mock('merchant/views/Settings/PaymentMethods', () => ({
  __esModule: true,
  default: () => <>Payment Methods Component</>,
}));

const renderApp = () => {
  render(<PaymentMethods />);
};

describe('PaymentMethods', () => {
  test('should render PaymentMethods', () => {
    renderApp();
    expect(screen.getByText('Dashboard Banner')).toBeInTheDocument();
    expect(screen.getByText('Test Mode Banner')).toBeInTheDocument();
    const paymentMethodsLink = screen.getByRole('link', { name: 'Payment Methods' });
    expect(paymentMethodsLink).toBeInTheDocument();
    expect(paymentMethodsLink).toHaveAttribute('href', ROUTES_INFO.PAYMENT_METHODS);
  });

  testBreadCrumb(renderApp, 'Payment Methods', ROUTES_INFO.PAYMENT_METHODS);

  test('should render PaymentMethods for Payment Methods route', () => {
    renderApp();
    const paymentMethodComponent = screen.getByTestId(ROUTES_INFO.PAYMENT_METHODS);
    expect(paymentMethodComponent).toBeInTheDocument();
    expect(paymentMethodComponent).toHaveTextContent('Payment Methods Component');
  });
});
