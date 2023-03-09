import React from 'react';
import { render, screen } from 'test-utils';
import { CardBody } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components';
import { getSubscriptionDataRes } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/__test__/mocks/response';
import { heading } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardBody/data';

describe('Tests for the CardBody component', () => {
  const renderApp = ({ shouldHaveUndefinedProps = false, initialState = {} } = {}) =>
    render(
      <CardBody
        subscriptionPlanData={
          shouldHaveUndefinedProps ? undefined : getSubscriptionDataRes().subscription
        }
      />,
      {
        initialState,
      },
    );

  test('Should not break with undefined props', () => {
    expect(() => renderApp({ shouldHaveUndefinedProps: true })).not.toThrowError();
  });

  test('Should not show the heading in the mobile UI', () => {
    const initialState = { app: { isMobileResolution: true } };
    renderApp({ initialState });

    expect(screen.queryByText(heading)).not.toBeInTheDocument();
  });

  test('Should show correct number of features in mobile', () => {
    const initialState = { app: { isMobileResolution: true } };
    renderApp({ initialState });

    expect(screen.getAllByTestId('featureDetail')).toHaveLength(9);
  });

  test('Should show correct number of features in desktop', () => {
    renderApp();

    expect(screen.getAllByTestId('featureDetail')).toHaveLength(9);
  });
});
