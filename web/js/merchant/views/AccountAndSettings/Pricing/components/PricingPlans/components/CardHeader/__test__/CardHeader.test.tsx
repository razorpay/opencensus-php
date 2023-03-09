import React from 'react';
import { render, screen } from 'test-utils';
import { CardHeader } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components';
import { getSubscriptionDataRes } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/__test__/mocks/response';
import { STATUS_DATA } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/data';
import { StatusDataT } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.types';

describe('Tests for the CardHeader component', () => {
  const renderApp = ({
    shouldHaveUndefinedProps = false,
    statusData = STATUS_DATA.LIVE,
    initialState = {},
  }: {
    shouldHaveUndefinedProps?: boolean;
    statusData?: StatusDataT;
    initialState?: Record<string, unknown>;
  } = {}) =>
    render(
      <CardHeader
        subscriptionPlanData={
          shouldHaveUndefinedProps ? undefined : getSubscriptionDataRes().subscription
        }
        statusData={statusData}
      />,
      {
        initialState,
      },
    );

  test('Should not break with undefined props', () => {
    expect(() => renderApp({ shouldHaveUndefinedProps: true })).not.toThrowError();
  });

  // Desktop UTs
  test('Should show correct plan details', () => {
    renderApp();

    expect(screen.getByText('Get onboard plan')).toBeInTheDocument();
    expect(screen.getByText('₹ 99/Month')).toBeInTheDocument();
  });

  test('Should show correct status details', () => {
    renderApp();

    expect(screen.getByText(STATUS_DATA.LIVE.label)).toBeInTheDocument();
  });

  test('Should hide the progress bar when the status is `In Progress`', () => {
    renderApp({ statusData: STATUS_DATA.IN_PROGRESS });

    expect(screen.queryByTestId('progressbarContainer')).not.toBeInTheDocument();
  });

  // Mobile UTs
  test('Should show correct plan details', () => {
    const initialState = { app: { isMobileResolution: true } };
    renderApp({ initialState });

    expect(screen.getByText('Get onboard plan')).toBeInTheDocument();
    expect(screen.getByText('₹ 99/Month')).toBeInTheDocument();
  });

  test('Should show correct status details', () => {
    const initialState = { app: { isMobileResolution: true } };
    renderApp({ initialState });

    expect(screen.getByText(STATUS_DATA.LIVE.label)).toBeInTheDocument();
  });

  test('Should hide the progress bar when the status is `In Progress`', () => {
    const initialState = { app: { isMobileResolution: true } };
    renderApp({ initialState, statusData: STATUS_DATA.IN_PROGRESS });

    expect(screen.queryByTestId('progressbarContainer')).not.toBeInTheDocument();
  });
});
