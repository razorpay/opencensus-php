import React from 'react';
import { render, userEvent, screen, waitFor } from 'test-utils';
import Pricing from 'merchant/views/AccountAndSettings/Pricing';
import { createLocation } from 'history';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

let location;

describe('Tests for the Pricing page', () => {
  beforeAll(() => {
    location = createLocation(ROUTES_INFO.PRICING_PLANS);
    location.push = jest.fn();
  });

  test('Location should change to pricing plans url when clicking the Pricing Plans tab', () => {
    render(<Pricing location={location} />);

    userEvent.click(screen.getByTestId('pricing-plan-link'));

    expect(location.pathname).toBe(ROUTES_INFO.PRICING_PLANS);
  });

  test('Should show the Pricing Plans tab when in corresponding location', async () => {
    jest.mock(
      'merchant/views/AccountAndSettings/Pricing/components/PricingPlans',
      () => (): JSX.Element => {
        return <div>Pricing Plans Component</div>;
      },
    );

    render(<Pricing location={location} />, {
      path: ROUTES_INFO.PRICING_PLANS,
      historyOptions: { initialEntries: [ROUTES_INFO.PRICING_PLANS] },
    });

    await waitFor(() => {
      expect(screen.getByText('Pricing Plans Component')).toBeInTheDocument();
    });
  });
});
