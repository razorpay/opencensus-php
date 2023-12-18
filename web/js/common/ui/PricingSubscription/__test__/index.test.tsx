import React from 'react';
import { render, waitFor } from 'test-utils';
import PricingSubscriptionWrapper, { PricingBundle } from 'common/ui/PricingSubscription/index';
import * as fetchPricingSubscription from 'merchant/reducers/growthService';
import { isExperimentActive } from 'common/utils/rzp-utils';

import { reduxState } from './PricingParentComponentsMockData';
describe('Tests for the Pricing Subscription', () => {
  const fetchPricingSubscriptionSpy = jest.spyOn(
    fetchPricingSubscription,
    'fetchPricingSubscription',
  );
  beforeEach(() => {
    fetchPricingSubscription.fetchPricingSubscription({ fromWhere: 'Home' });
  });
  const finalProps = {
    ...reduxState,
    fetchPricingSubscription: jest.fn(),
    openModal: jest.fn(),
    isMobileResolution: Boolean,
  };
  const maxImpressions = 10;
  const pricingApp = () => <PricingSubscriptionWrapper />;
  test('Parent component should render successfully', () => {
    expect(() => render(<PricingSubscriptionWrapper />)).not.toThrowError();
  });
  test('Child component should render with Props', () => {
    expect(() => render(<PricingBundle {...finalProps} />)).not.toThrowError();
  });
  test('user allow to make api call', () => {
    const { user, mode } = finalProps;
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {
        variables: {
          result: 'off',
        },
      },
    };
    const isStreaksExperimentEnabled = isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH);
    const impressionCount = Math.floor(Math.random() * 6);
    const isWithinTimeInterval = false;
    const isNotInterested = 0;
    const isAllowedToFetch =
      !isStreaksExperimentEnabled &&
      user?.isBundlePricingEnabled &&
      mode === 'live' &&
      impressionCount <= maxImpressions &&
      !isWithinTimeInterval &&
      !isNotInterested;
    expect(isAllowedToFetch).toBeTruthy();
  });
  test('user not allow to make api call', () => {
    const { user } = finalProps;
    let { mode } = finalProps;
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {
        variables: {
          result: 'on',
        },
      },
    };
    const isStreaksExperimentEnabled = isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH);
    user.isBundlePricingEnabled = false;
    mode = 'test';
    const impressionCount = Math.floor(Math.random() * 10) + 6;
    const isWithinTimeInterval = true;
    const isNotInterested = 1;
    const isAllowedToFetch =
      !isStreaksExperimentEnabled &&
      user?.isBundlePricingEnabled &&
      mode === 'live' &&
      impressionCount < maxImpressions &&
      !isWithinTimeInterval &&
      !isNotInterested;
    expect(isAllowedToFetch).toBeFalsy();
  });

  test('should call fetch items on mount', async () => {
    pricingApp();
    await waitFor(() => {
      expect(fetchPricingSubscriptionSpy).toHaveBeenCalledTimes(1);
    });
  });
});
