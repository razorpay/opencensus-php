import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import { deepClone } from 'common/utils/rzp-utils';
import { render, screen } from 'test-utils';

import {
  App,
  PAYU_PROVIDER,
  PAYTM_PROVIDER,
  NETBANKING_AXIS_PROVIDER,
  CKO_PROVIDER,
  OPTIMIZER_RAZORPAY_PROVIDER,
} from './mocks/Step3';
import { TPV_OPTIONS } from './mocks/constants';

describe('Add Provider Step 3 Screen', () => {
  const renderApp = (props = {}) => {
    render(<App {...props} />);
  };

  describe('For payu gateway', () => {
    const FIELDS = ['Key', 'Salt', 'Payment Methods'];

    test('should render without any errors', () => {
      expect(() => renderApp(PAYU_PROVIDER)).not.toThrowError();
    });

    test.each(FIELDS)('should rendered the requried fields: %s', (field) => {
      renderApp(PAYU_PROVIDER);
      expect(screen.getByText(field)).toBeInTheDocument();
    });

    test('should render Recurring field', () => {
      // Recurring field will be visible only if card or upi method is enabled
      PAYU_PROVIDER.provider.Gateway_details['Payment Methods'] = ['card'];
      renderApp(PAYU_PROVIDER);
      expect(screen.getByText('Recurring')).toBeInTheDocument();
    });
  });

  describe('For paytm gateway', () => {
    const FIELDS = [
      'INDUSTRY_TYPE_ID',
      'KEY',
      'MID',
      'Payment Methods',
      'WEBSITE',
      'Wallet auto-debit',
    ];

    test('should render without any errors', () => {
      expect(() => renderApp(PAYTM_PROVIDER)).not.toThrowError();
    });

    test('should have wallet auto debit disabled', () => {
      renderApp(PAYTM_PROVIDER);
      expect(screen.getByText('Disabled')).toBeInTheDocument();
    });

    test.each(FIELDS)('should render the required field: %s', (field) => {
      renderApp(PAYTM_PROVIDER);
      expect(screen.getByText(field)).toBeInTheDocument();
    });

    test('should show wallet auto-debit enabled', () => {
      const props = deepClone(PAYTM_PROVIDER);
      props.provider.Gateway_details.ENABLE_AUTO_DEBIT = true;
      renderApp(props);
      expect(screen.getByText('Enabled')).toBeInTheDocument();
    });

    const AUTO_DEBIT_FIELDS = ['Client Key', 'Client Secret'];

    test.each(AUTO_DEBIT_FIELDS)(
      'should render required field %s for wallet auto debit',
      (field) => {
        const props = deepClone(PAYTM_PROVIDER);
        props.provider.Gateway_details.ENABLE_AUTO_DEBIT = true;
        renderApp(props);
        expect(screen.getByText(field)).toBeInTheDocument();
      },
    );
  });

  describe('For netbanking axis gateway', () => {
    const FIELDS = ['Merchant Id', 'Payment Methods', 'TPV'];

    test('should render without any errors', () => {
      expect(() => renderApp(NETBANKING_AXIS_PROVIDER)).not.toThrowError();
    });

    test.each(FIELDS)('should rendered the requried fields: %s', (field) => {
      renderApp(NETBANKING_AXIS_PROVIDER);
      expect(screen.getByText(field)).toBeInTheDocument();
    });

    test.each(TPV_OPTIONS)('should render tpv option: %s', (option) => {
      renderApp(NETBANKING_AXIS_PROVIDER);
      expect(screen.getByText(option)).toBeInTheDocument();
    });
  });

  describe('For checkout.com gateway', () => {
    const FIELDS = [
      'Client ID',
      'Client Secret',
      'Payment Methods',
      'Scope',
      'Grant Type',
      'Processing Channel ID',
    ];

    test('should render without any errors', () => {
      expect(() => renderApp(CKO_PROVIDER)).not.toThrowError();
    });

    test.each(FIELDS)('should rendered the requried fields: %s', (field) => {
      renderApp(CKO_PROVIDER);
      expect(screen.getByText(field)).toBeInTheDocument();
    });
  });

  describe('For optimizer_razorpay gateway', () => {
    const FIELDS = ['Key', 'Secret', 'Payment Methods'];

    test('should render without any errors', () => {
      expect(() => renderApp(OPTIMIZER_RAZORPAY_PROVIDER)).not.toThrowError();
    });

    test.each(FIELDS)('should rendered the requried fields: %s', (field) => {
      renderApp(OPTIMIZER_RAZORPAY_PROVIDER);
      expect(screen.getByText(field)).toBeInTheDocument();
    });

    test('should not render Gateway Acquirer field', () => {
      renderApp(OPTIMIZER_RAZORPAY_PROVIDER);
      expect(screen.queryByText('Gateway Acquirer')).not.toBeInTheDocument();
    });
  });
});
