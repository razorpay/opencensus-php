import { screen } from 'test-utils';
import { renderApp } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/StandardForm';

describe('Payment Link - Standard form', () => {
  test('should render "Payer Name"', () => {
    renderApp();
    expect(screen.getByText('Payer Name')).toBeInTheDocument();
  });
});
