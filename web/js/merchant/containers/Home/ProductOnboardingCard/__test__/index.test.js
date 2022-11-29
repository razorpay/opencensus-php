import React from 'react';
import { render, userEvent, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import store, { storeWithInitialState } from 'merchant/store';
import ProductOnboardingCard from '..';
import merge from 'lodash/merge';
// Mock `window.location` with Jest spies and extend expect
import 'jest-location-mock';
import * as analytics from 'common/utils/analytics';

let analyticsTrackSpy;

const globalState = store.getState();
const user = {
  ...globalState.session.user,
  contact_name: 'Test Merchant',
};

const getInitialState = ({ userDetails = {}, hasMerchantTrasacted = false } = {}) => {
  return {
    ...globalState,
    session: {
      ...globalState.session,
      user: merge(
        {
          ...globalState.session.user,
          ...user,
        },
        userDetails,
      ),
    },
    transactionAmount: {
      ...(hasMerchantTrasacted
        ? {
            ...globalState.transactionAmount,
            loading: false,
            amount: 200,
          }
        : globalState.transactionAmount),
    },
  };
};

describe('ProductOnboardingCard - index', () => {
  const renderApp = ({ initialState }) =>
    render(
      <Provider store={storeWithInitialState(initialState)}>
        <ProductOnboardingCard />
      </Provider>,
    );

  beforeAll(() => {
    document.execCommand = jest.fn();
    analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render component without errors', () => {
    const initialState = getInitialState();
    expect(() => renderApp({ initialState })).not.toThrowError();
  });

  test('should show PG, PB, PH cards for PG non-activated merchant', () => {
    const initialState = getInitialState({
      userDetails: {
        activation_status: 'under_review',
        business_website: 'https://razorpay.com',
      },
    });
    const { getByTestId } = renderApp({ initialState });

    expect(getByTestId('payment-gateway-card')).toBeVisible();
    expect(getByTestId('payment-buttons-card')).toBeVisible();
    expect(getByTestId('payment-handle-card')).toBeVisible();
  });

  test('should show PH, PB, PL cards for PG non-activated merchant', () => {
    const initialState = getInitialState({
      userDetails: {
        activation_status: 'under_review',
      },
    });
    const { getByTestId } = renderApp({ initialState });

    expect(getByTestId('payment-handle-card')).toBeVisible();
    expect(getByTestId('payment-buttons-card')).toBeVisible();
    expect(getByTestId('payment-links-card')).toBeVisible();
  });

  test('should show Quick Actions cards for PG non-activated merchant', () => {
    const initialState = getInitialState({
      userDetails: {
        activation_status: 'activated',
      },
      hasMerchantTrasacted: true,
    });
    const { getByTestId } = renderApp({ initialState });

    expect(getByTestId('quick-action-links-card')).toBeVisible();
    expect(getByTestId('quick-action-share-card')).toBeVisible();
  });

  test('should trigger event on customize', async () => {
    const initialState = getInitialState({
      userDetails: {
        activation_status: 'activated',
        business_website: 'https://razorpay.com',
      },
      hasMerchantTrasacted: true,
    });
    const { getByRole } = renderApp({ initialState });

    await waitFor(() => {
      expect(getByRole('button', { name: /customize/i })).toBeEnabled();
    });
    await userEvent.click(getByRole('button', { name: /customize/i }));
    await waitFor(() => {
      expect(analyticsTrackSpy).toHaveBeenCalledTimes(1);
    });
  });

  test('should navigate on create payment link', async () => {
    const initialState = getInitialState({
      userDetails: {
        activation_status: 'activated',
        business_website: 'https://razorpay.com',
      },
      hasMerchantTrasacted: true,
    });
    const { getByRole } = renderApp({ initialState });

    await waitFor(() => {
      expect(getByRole('button', { name: /create payment link/i })).toBeEnabled();
    });
    await userEvent.click(getByRole('button', { name: /create payment link/i }));
  });

  test('should copy link on copy Handle click', async () => {
    const initialState = getInitialState({
      userDetails: {
        activation_status: 'activated',
        business_website: 'https://razorpay.com',
      },
      hasMerchantTrasacted: true,
    });
    const { getByRole } = renderApp({ initialState });

    await waitFor(() => {
      expect(getByRole('button', { name: /copy link/i })).toBeEnabled();
    });

    const cta = getByRole('button', { name: /copy link/i });
    await userEvent.click(cta);
    await userEvent.click(cta);
    expect(analyticsTrackSpy).toHaveBeenCalledTimes(1);
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Dashboard PH Copy',
        properties: { product: 'PG', section: 'QuickAction' },
      }),
    );

    await waitFor(
      () => {
        expect(getByRole('button', { name: /copy link/i })).toBeEnabled();
      },
      { timeout: 2000 },
    );
  });
});
