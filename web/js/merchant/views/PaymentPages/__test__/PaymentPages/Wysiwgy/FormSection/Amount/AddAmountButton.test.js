import { screen } from 'test-utils';
import {
  renderApp,
  hideDynamicPriceField,
} from 'merchant/views/PaymentPages/__test__/mocks/fixtures/AddAmountButton';

const dynamicAmountFieldLabel = 'Customers Decide Amount';

describe('Payment Page - Add Amount', () => {
  test('should have "Customers Decide Amount" if org feature flag "hide_dynamic_price_pp" is disabled.', () => {
    hideDynamicPriceField(false);
    renderApp();
    expect(screen.queryByText(dynamicAmountFieldLabel)).toBeInTheDocument();
  });

  test('should not have "Customers Decide Amount" if org feature flag "hide_dynamic_price_pp" is enabled.', () => {
    hideDynamicPriceField(true);
    renderApp();
    expect(screen.queryByText(dynamicAmountFieldLabel)).not.toBeInTheDocument();
  });
});
