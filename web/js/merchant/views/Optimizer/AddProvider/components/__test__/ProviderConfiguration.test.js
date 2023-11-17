import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

import '@testing-library/jest-dom/extend-expect';
import { deepClone, titleCase } from 'common/utils/rzp-utils';
import {
  PAYU_PROVIDER,
  PAYTM_PROVIDER,
  NETBANKING_AXIS_PROVIDER,
  CKO_PROVIDER,
} from 'merchant/views/Navigator/components/AddProvider/components/__test__/mocks/Step3';
import { SUPPORTED_GATEWAYS } from 'merchant/views/Navigator/components/AddProvider/components/__test__/mocks/constants';
import ProviderConfiguration from 'merchant/views/Optimizer/AddProvider/components/ProviderConfiguration';
import { render, screen } from 'test-utils';

describe('Add Provider > ProviderConfiguration', () => {
  let mockProps;

  beforeEach(() => {
    mockProps = {
      isEdit: false,
      providers: SUPPORTED_GATEWAYS,
      provider: {
        Gateway: 'payu',
        Gateway_details: {
          'Payment Methods': [],
        },
      },
    };

    console.error = jest.fn(); // Silence error messages
    console.warn = jest.fn(); // Silence warning messages
  });

  afterEach(() => {
    console.error.mockRestore(); // Restore the original console.error
    console.warn.mockRestore(); // Restore the original console.warn
  });

  const App = (props = {}) => {
    return (
      <BladeProvider themeTokens={paymentTheme}>
        <ProviderConfiguration {...props} />
      </BladeProvider>
    );
  };

  it('should render ProviderConfiguration without any errors', () => {
    expect(() => <App {...mockProps} />).not.toThrowError();
  });

  it('should render ProviderConfiguration headingText and subText', () => {
    render(<App {...mockProps} selectedProvider="payu" />);
    expect(screen.getByText(/PayU Production API Details/)).toBeInTheDocument();
  });

  describe('For payu gateway', () => {
    const FIELDS = ['Key', 'Salt', 'Payment Methods'];

    it('should render without any errors', () => {
      expect(() => render(<App {...PAYU_PROVIDER} />)).not.toThrowError();
    });

    test.each(FIELDS)('should rendered the requried fields: %s', (field) => {
      render(<App {...PAYU_PROVIDER} />);
      expect(screen.getByText(field)).toBeInTheDocument();
    });
  });

  describe('For paytm gateway', () => {
    const FIELDS = ['INDUSTRY_TYPE_ID', 'KEY', 'MID', 'Payment Methods', 'WEBSITE'];
    const AUTO_DEBIT_FIELDS = ['Client Key', 'Client Secret'];

    it('should render without any errors', () => {
      expect(() => <App {...PAYTM_PROVIDER} />).not.toThrowError();
    });

    test.each(FIELDS)('should render the required field: %s', (field) => {
      render(<App {...PAYTM_PROVIDER} />);
      expect(screen.getByText(titleCase(field))).toBeInTheDocument();
    });

    it('should have wallet auto debit disabled', () => {
      render(<App {...PAYTM_PROVIDER} isPaytmAutoDebitEnabled={true} />);
      expect(screen.getByText('Wallet auto-debit')).toBeInTheDocument();
      expect(screen.getByText('Disabled')).toBeInTheDocument();
    });

    it('should show wallet auto-debit enabled', () => {
      const props = deepClone(PAYTM_PROVIDER);
      props.provider.Gateway_details.ENABLE_AUTO_DEBIT = true;
      render(<App {...props} isPaytmAutoDebitEnabled={true} />);
      expect(screen.getByText('Wallet auto-debit')).toBeInTheDocument();
      expect(screen.getByText('Enabled')).toBeInTheDocument();
    });

    test.each(AUTO_DEBIT_FIELDS)(
      'should render required field %s for wallet auto debit',
      (field) => {
        const props = deepClone(PAYTM_PROVIDER);
        props.provider.Gateway_details.ENABLE_AUTO_DEBIT = true;
        render(<App {...props} isPaytmAutoDebitEnabled={true} />);
        expect(screen.getByText(titleCase(field))).toBeInTheDocument();
      },
    );
  });

  describe('For netbanking axis gateway', () => {
    const FIELDS = ['Merchant Id', 'Payment Methods', 'TPV'];

    it('should render without any errors', () => {
      expect(() => render(<App {...NETBANKING_AXIS_PROVIDER} />)).not.toThrowError();
    });

    test.each(FIELDS)('should rendered the requried fields: %s', (field) => {
      render(<App {...NETBANKING_AXIS_PROVIDER} />);
      expect(screen.getByText(field)).toBeInTheDocument();
    });

    test.each([
      ['Non TPV', 0],
      ['TPV Only', 1],
      ['Both (TPV and Non TPV)', 2],
    ])('should render tpv option: %s', (option, value) => {
      const props = deepClone(NETBANKING_AXIS_PROVIDER);
      props.provider.Gateway_details.TPV = value;

      render(<App {...props} />);
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

    it('should render without any errors', () => {
      expect(() => render(<App {...CKO_PROVIDER} />)).not.toThrowError();
    });

    test.each(FIELDS)('should rendered the requried fields: %s', (field) => {
      render(<App {...CKO_PROVIDER} />);
      expect(screen.getByText(titleCase(field))).toBeInTheDocument();
    });
  });
});
