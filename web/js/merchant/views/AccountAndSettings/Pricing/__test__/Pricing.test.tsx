import React from 'react';
import { render, userEvent, screen, waitFor } from 'test-utils';
import Pricing from 'merchant/views/AccountAndSettings/Pricing';
import { createMemoryHistory } from 'history';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

jest.mock(
  'merchant/views/AccountAndSettings/Pricing/components/PricingPlans',
  () => (): JSX.Element => {
    return <div>Pricing Plans Component</div>;
  },
);

describe('Tests for the Pricing page', () => {
  const renderApp = () => {
    const history = createMemoryHistory({ initialEntries: [ROUTES_INFO.PRICING_PLANS] });

    return render(<Pricing location={history.location} />, {
      path: ROUTES_INFO.PRICING_PLANS,
      historyOptions: { initialEntries: [ROUTES_INFO.PRICING_PLANS] },
    });
  };

  test('Location should change to pricing plans url when clicking the Pricing Plans tab', async () => {
    const { history } = renderApp();

    await userEvent.click(screen.getByTestId('flex-link'));

    expect(history?.location?.pathname).toBe(ROUTES_INFO.PRICING_PLANS);
  });

  test('Should show the Pricing Plans tab when in corresponding location', async () => {
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('Pricing Plans Component')).toBeInTheDocument();
    });
  });
});
