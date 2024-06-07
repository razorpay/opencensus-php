import React from 'react';
import { screen } from '@testing-library/react';

import { render } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import { PAYMENT_METHODS } from 'merchant/views/Offers/constants';

import ApplicableOn from '../ApplicableOn';

const defaultProps = {
  formData: {
    payment_method: '',
    issuer: '',
    payment_method_type: '',
    payment_network: '',
    max_payment_count: '',
    iins: [],
  },
  isFormLocked: false,
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
      formData: {
        ...defaultProps.formData,
        payment_method: PAYMENT_METHODS.Wallet,
      },
    };
    renderApp(props);
    expect(screen.getByText('Issuer')).toBeInTheDocument();
  });

  it('should render Card Less EMI issuer select when payment method is CardLessEmi', () => {
    const props = {
      formData: {
        ...defaultProps.formData,
        payment_method: PAYMENT_METHODS.CardLessEmi,
      },
    };
    renderApp(props);
    expect(screen.getByText('Issuer')).toBeInTheDocument();
  });

  it('should render card related inputs when payment method is Card', () => {
    const props = {
      formData: {
        ...defaultProps.formData,
        payment_method: PAYMENT_METHODS.Card,
      },
    };
    renderApp(props);
    expect(screen.getByText('Card Type')).toBeInTheDocument();
    expect(screen.getByText('Bank')).toBeInTheDocument();
  });

  it('should render EMI related inputs when payment method is EMI', () => {
    const props = {
      formData: {
        ...defaultProps.formData,
        payment_method: PAYMENT_METHODS.EMI,
      },
    };
    renderApp(props);
    expect(screen.getByText('Card Type')).toBeInTheDocument();
  });

  it('should render Net Banking issuer select when payment method is NetBanking', () => {
    const props = {
      formData: {
        ...defaultProps.formData,
        payment_method: PAYMENT_METHODS.NetBanking,
      },
    };
    renderApp(props);
    expect(screen.getByText('Issuer')).toBeInTheDocument();
  });
});
