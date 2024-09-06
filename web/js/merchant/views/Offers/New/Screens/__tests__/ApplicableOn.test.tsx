import React from 'react';
import { screen } from '@testing-library/react';

import { render } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { OFFER_TYPES, PAYMENT_METHODS, UPI_APP_PROVIDERS } from 'merchant/views/Offers/constants';

import ApplicableOn from '../ApplicableOn';

jest.mock('common/splitz', () => ({
  withSplitzService: (Component) => (props) =>
    (
      <Component
        {...props}
        splitz={{
          abExperiments: {
            upi_granular_offer_dashboard: {
              variables: {
                result: 'on',
              },
            },
          },
        }}
      />
    ),
  useSplitzService: () => ({
    abExperiments: {},
  }),
}));

const defaultProps = {
  values: {
    payment_method: '',
    issuer: '',
    payment_method_type: '',
    payment_network: '',
    max_payment_count: '',
    iins: [],
  },
  isFormLocked: false,
  errors: {},
  touched: {},
};

const renderApp = (props = {}) => {
  return render(<ApplicableOn {...defaultProps} {...props} />);
};

describe('ApplicableOn Component', () => {
  it('should render Payment Method select input', () => {
    renderApp();
    expect(screen.getByText('Payment Method')).toBeInTheDocument();
  });
  it('should render Wallet issuer select when payment method is Wallet', () => {
    const props = {
      values: {
        ...defaultProps.values,
        payment_method: PAYMENT_METHODS.Wallet,
      },
    };
    renderApp(props);
    expect(screen.getByText('Issuer')).toBeInTheDocument();
  });

  it('should render Card Less EMI issuer select when payment method is CardLessEmi', () => {
    const props = {
      values: {
        ...defaultProps.values,
        payment_method: PAYMENT_METHODS.CardLessEmi,
      },
    };
    renderApp(props);
    expect(screen.getByText('Issuer')).toBeInTheDocument();
  });

  it('should render card related inputs when payment method is Card', () => {
    const props = {
      values: {
        ...defaultProps.values,
        payment_method: PAYMENT_METHODS.Card,
      },
    };
    renderApp(props);
    expect(screen.getByText('Card Type')).toBeInTheDocument();
    expect(screen.getByText('Bank')).toBeInTheDocument();
  });

  it('should render EMI related inputs when payment method is EMI', () => {
    const props = {
      values: {
        ...defaultProps.values,
        payment_method: PAYMENT_METHODS.EMI,
      },
    };
    renderApp(props);
    expect(screen.getByText('Card Type')).toBeInTheDocument();
  });

  it('should render Net Banking issuer select when payment method is NetBanking', () => {
    const props = {
      values: {
        ...defaultProps.values,
        payment_method: PAYMENT_METHODS.NetBanking,
      },
    };
    renderApp(props);
    expect(screen.getByText('Issuer')).toBeInTheDocument();
  });
  it('should render Granular Offer inputs when payment_method is UPI and offer type is Cashback', () => {
    const props = {
      values: {
        ...defaultProps.values,
        payment_method: PAYMENT_METHODS.UPI,
        type: OFFER_TYPES.Cashback,
        upiApps: UPI_APP_PROVIDERS.ALL,
        upiAppsList: [],
        payerAccountTypes: [],
      },
    };
    renderApp(props);

    expect(screen.getByTestId('upi-apps')).not.toBe(null);
    expect(screen.getByTestId('payer-account-types')).not.toBe(null);
  });
});
