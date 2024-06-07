import React from 'react';
import { screen, fireEvent } from '@testing-library/react';
import { Provider } from 'react-redux';

import store from 'merchant/store';
import { render } from 'test-utils';

import OffersOnBoarding, { getIsAllowedResetOffersOnBoarding, getIsOffersEnabled } from '../index';

describe('OffersOnBoarding Component', () => {
  beforeEach(() => {
    window.rzpQ = {
      component: jest.fn(),
      productOnboarding: () => ({
        initiated: jest.fn(), // Mock initiated function
        success: jest.fn(),
      }),
    };
  });

  it('should render the onboarding component correctly', () => {
    render(
      <Provider store={store}>
        <OffersOnBoarding active={0} />
      </Provider>,
    );

    expect(screen.getByText('Offers')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Now run customer offers via the Razorpay dashboard. Businesses have seen a 35% increase in sales thanks to Razorpay Offers.',
      ),
    ).toBeInTheDocument();
  });

  it('should call closeOnboarding when SkipAndGetStartedButton is clicked', () => {
    const closeOnboardingMock = jest.fn();
    render(
      <Provider store={store}>
        <OffersOnBoarding active={0} closeOnboarding={closeOnboardingMock} />
      </Provider>,
    );

    const button = screen.getByText('Skip And Get Started');
    fireEvent.click(button);

    expect(closeOnboardingMock).toHaveBeenCalled();
  });

  it('should return false if offers are present or loading is true', () => {
    const offers = [1];
    const loading = true;
    expect(getIsAllowedResetOffersOnBoarding(offers, loading)).toBe(false);
  });

  it('should return true if user.isOffersEnabled is true', () => {
    const state = {
      user: { isOffersEnabled: true },
      offers: { loading: false, items: [] },
    };
    expect(getIsOffersEnabled(state)).toBe(true);
  });

  it('should return true if offers are loading', () => {
    const state = {
      user: { isOffersEnabled: false },
      offers: { loading: true, items: [] },
    };
    expect(getIsOffersEnabled(state)).toBe(true);
  });

  it('should setOffersOnboardingData and return user.isOffersEnabled if offers.items are present', () => {
    const state = {
      user: { isOffersEnabled: false },
      offers: { loading: false, items: [1] },
    };
    expect(getIsOffersEnabled(state)).toBe(false);
  });

  it('should return user.isOffersEnabled if no offers.items and not loading', () => {
    const state = {
      user: { isOffersEnabled: false },
      offers: { loading: false, items: [] },
    };
    expect(getIsOffersEnabled(state)).toBe(false);
  });
});
