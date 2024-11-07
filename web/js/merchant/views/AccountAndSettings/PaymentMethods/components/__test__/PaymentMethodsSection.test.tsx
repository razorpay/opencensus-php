import { screen, render, userEvent } from 'test-utils';
import PaymentMethodsSection from 'merchant/views/AccountAndSettings/PaymentMethods/components/Section';
import React from 'react';
import {
  PaymentMethodsFields,
  PaymentMethodsTitles,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import { initialState as instrumentRequestsState } from 'merchant/reducers/instrumentRequests';
import * as track from 'merchantLA/containers/TestModeBanner/ga';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

jest.mock('merchant/views/Settings/PaymentMethods/components/IntermediateList', () => ({
  __esModule: true,
  default: ({ instrument }) => {
    return <p>Intermediate List: {instrument.intermediateList.length}</p>;
  },
}));

const trackLinkSpy = jest.spyOn(track, 'trackLinkClick');

const defaultProps = {
  type: PaymentMethodsFields.INTERNATIONAL,
};

const defaultInitialState = {
  session: {
    user: {
      activation_status: 'activated',
    },
  },
  instrumentRequests: {
    pg: instrumentRequestsState.pg,
    intermediateInstrument: null,
    loading: false,
  },
};

const renderApp = ({ props = {}, initialState = {} } = {}) =>
  render(<PaymentMethodsSection {...defaultProps} {...props} />, {
    initialState: {
      ...defaultInitialState,
      ...initialState,
    },
  });

describe('PaymentMethodsSection', () => {
  test('should render Payment Methods Section info', () => {
    renderApp();
    expect(screen.queryByTestId('payment-method-tabs-shimmer')).not.toBeInTheDocument();
    expect(screen.getByText('International Payments')).toBeInTheDocument();
    expect(screen.getByText('Cards, PayPal, USD ACH & more')).toBeInTheDocument();
    expect(
      screen.queryByText(/KYC verification is mandatory to request for new payment methods/i),
    ).not.toBeInTheDocument();
    expect(screen.getByText('Cards, PayPal, USD ACH & more')).toBeInTheDocument();

    const paymentMethodsLink = screen.getByRole('link', {
      name: 'Know More about payment methods',
    });
    expect(paymentMethodsLink).toHaveAttribute(
      'href',
      'https://razorpay.com/docs/payment-gateway/dashboard-guide/settings/payment-methods/',
    );
  });

  test('should show activation form link if user is not activated', async () => {
    renderApp({
      initialState: {
        ...defaultInitialState,
        session: {
          user: {},
        },
      },
    });

    expect(
      screen.getByText(/KYC verification is mandatory to request for new payment methods/i),
    ).toBeInTheDocument();

    const kycActivationFormLink = screen.getByRole('link', { name: 'activation form' });
    await userEvent.click(kycActivationFormLink);
    expect(trackLinkSpy).toHaveBeenCalledWith('Go To - Activation Form');
  });

  test('should call analytics on clicking know More', async () => {
    renderApp();
    const paymentMethodsLink = screen.getByRole('link', {
      name: 'Know More about payment methods',
    });
    await userEvent.click(paymentMethodsLink);
    expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith({
      objectName: 'know more',
      actionName: 'clicked',
      screen: 'Payment Methods',
      properties: {
        location: PaymentMethodsTitles[defaultProps.type],
      },
    });
  });

  describe('Shimmer', () => {
    test('should be shown if showLoader is true', () => {
      renderApp({
        props: {
          ...defaultProps,
          showLoader: true,
        },
      });
      expect(screen.getByTestId('payment-method-tabs-shimmer')).toBeInTheDocument();
    });

    test('should be shown if loading is true', () => {
      renderApp({
        initialState: {
          instrumentRequests: {
            ...defaultInitialState.instrumentRequests,
            loading: true,
          },
        },
      });
      expect(screen.getByTestId('payment-method-tabs-shimmer')).toBeInTheDocument();
    });
  });

  test('should show IntermediateList if it exists', () => {
    renderApp({
      initialState: {
        instrumentRequests: {
          ...defaultInitialState.instrumentRequests,
          intermediateInstrument: instrumentRequestsState.pg[2],
        },
      },
      props: {
        type: PaymentMethodsFields.CARDS,
      },
    });
    expect(screen.getByText(/Intermediate List: 3/)).toBeInTheDocument();
  });

  test('should render correct banner if merchant is non-live', () => {
    renderApp({
      initialState: {
        ...defaultInitialState,
        session: {
          user: {
            live: false,
          },
        },
      },
      props: {
        type: PaymentMethodsFields.CARDS,
      },
    });
    expect(screen.getByTestId('non-live-banner')).toHaveTextContent(
      'Request for Payment methods is unavailable as your account is not enabled to accept transactions',
    );
  });
});
