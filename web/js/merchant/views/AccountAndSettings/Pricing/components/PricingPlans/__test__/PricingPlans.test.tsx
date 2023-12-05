import React from 'react';
import { render, screen, server, waitFor } from 'test-utils';
import PricingPlans from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans';
import { getState } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/__test__/mocks/fixtures';
import {
  fetchEnrollmentStatusHandler,
  fetchSubscriptionsDataHandler,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/__test__/mocks/handlers';
import track from 'react-tracking';
import * as fetchEnrollmentStatus from 'merchant/reducers/bundlePricing';
import { defaultErrorMessage } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/data';

const TrackedPricingPlans = track()(PricingPlans);
const renderApp = (initialState) => render(<TrackedPricingPlans />, { initialState });

describe('Tests for the Pricing Plans page', () => {
  test('Should call the exists API only once when mounted', () => {
    const fetchEnrollmentStatusSpy = jest.spyOn(fetchEnrollmentStatus, 'fetchEnrollmentStatus');
    const initialState = getState();
    renderApp(initialState);

    expect(fetchEnrollmentStatusSpy).toHaveBeenCalledTimes(1);
  });

  test('Should show default error message if the subscriptions API fails', async () => {
    server.use(fetchEnrollmentStatusHandler({ exists: 'true', delay: 0 }));
    // api level status is somethings always sent back in res if data is there
    server.use(
      fetchSubscriptionsDataHandler({ delay: 0, gsLevelStatus: 400, apiLevelStatus: 200 }),
    );

    const initialState = getState();
    renderApp(initialState);

    const errorMessageEl = await waitFor(() => screen.findByText(defaultErrorMessage));

    expect(errorMessageEl).toBeInTheDocument();
  });

  test('Should show the loader when exists API is being called', async () => {
    server.use(fetchEnrollmentStatusHandler());
    const initialState = getState();
    renderApp(initialState);

    const loaderContainerEl = await waitFor(() => screen.findByTestId('LoaderContainer'));
    const progressBarEl = await waitFor(() => screen.findByRole('progressbar'));

    expect(loaderContainerEl).toBeInTheDocument();
    expect(progressBarEl).toBeInTheDocument();
  });

  test('Should show the error message if exists API returns false', async () => {
    const message = 'Subscription not active';
    server.use(fetchEnrollmentStatusHandler({ exists: 'false', message, delay: 0 }));
    server.use(fetchSubscriptionsDataHandler({ delay: 0 }));
    const initialState = getState();
    renderApp(initialState);

    await waitFor(() => {
      expect(screen.getByText(message)).toBeInTheDocument();
    });
  });

  test('Should show default error message if status cannot be determined', async () => {
    server.use(fetchEnrollmentStatusHandler({ exists: 'true', delay: 0 }));
    server.use(fetchSubscriptionsDataHandler({ delay: 0, subscriptionStatus: 'anything' }));
    const initialState = getState();
    renderApp(initialState);

    const errorMessageEl = await waitFor(() => screen.findByText(defaultErrorMessage));

    expect(errorMessageEl).toBeInTheDocument();
  });
});
