import React from 'react';
import PricingSectionMweb from 'common/ui/PricingSubscription/Mobile/PricingSectionMweb';
import {
  pricing_bundle,
  finalProps,
} from 'common/ui/PricingSubscription/__test__/PricingParentComponentsMockData';
import { render, screen, userEvent } from 'test-utils';

describe('Tests for `PricingSectionMweb` components', () => {
  const renderApp = () => render(<PricingSectionMweb {...finalProps} />);
  test('`Data from props`: Should show the pricing plan section content', () => {
    renderApp();
    expect(screen.getByText(finalProps.plans.title)).toBeInTheDocument();
    expect(screen.getAllByText(finalProps.plans.button.label)).not.toHaveLength(0);
    expect(
      screen.getByText(`₹${finalProps.plans.monthlyPrice.toLocaleString()}/Month`),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        `₹${Math.floor(finalProps.plans.annualPrice / 12).toLocaleString()}/Month with Annual Plan`,
      ),
    ).toBeInTheDocument();
  });

  test('`Data from props`: Should show the view all benefits button', () => {
    renderApp();
    expect(screen.getByText('View All Benefits')).toBeInTheDocument();
  });

  test.each(pricing_bundle.featureIdOrder)(
    '`Data from props`: Should show the full plan information if `View All Benefits` cta is clicked',
    async () => {
      renderApp();
      const viewAllBenefitsCTA = screen.getByText('View All Benefits');

      await userEvent.click(viewAllBenefitsCTA);

      expect(screen.getByText(finalProps.plans.title)).toBeInTheDocument();
      expect(screen.getAllByText(finalProps.plans.button.label)).not.toHaveLength(0);
      expect(
        screen.getByText(`₹${finalProps.plans.monthlyPrice.toLocaleString()}/Month`),
      ).toBeInTheDocument();
      expect(
        screen.getByText(
          `₹${Math.floor(
            finalProps.plans.annualPrice / 12,
          ).toLocaleString()}/Month with Annual Plan`,
        ),
      ).toBeInTheDocument();
    },
  );
});
