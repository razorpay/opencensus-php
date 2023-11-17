import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

import { deepClone } from 'common/utils/rzp-utils';
import { GATEWAY_CATEGORIES } from 'merchant/views/Navigator/constants';
import SelectGateway from 'merchant/views/Optimizer/AddProvider/components/SelectGateway';
import { render, screen, waitFor, fireEvent } from 'test-utils';

const mockProps = {
  isEdit: false,
  isFormEdit: true,
  providers: {
    payu: {
      'Gateway Name': { data_value: 'PayU' },
      'Payment Methods': {
        data_value: ['card', 'upi', 'netbanking', 'emi', 'wallet', 'emandate', 'sodexo'],
      },
      optimizer_seamless_disabled: false,
    },
    paytm: {
      'Gateway Name': { data_value: 'PayTm' },
      'Payment Methods': {
        data_value: ['card', 'upi', 'netbanking', 'wallet'],
      },
      optimizer_seamless_disabled: false,
    },
    checkout_dot_com_optimizer: {
      'Gateway Name': { data_value: 'Checkout.com' },
      'Payment Methods': {
        data_value: ['card'],
      },
    },
  },
  categorizedProviders: {},
  loadingProviders: false,
  selectedProvider: null,
  validateStep: jest.fn(),
};

describe('Add Provider SelectGateway component', () => {
  const App = (props = {}) => {
    return (
      <BladeProvider themeTokens={paymentTheme}>
        <SelectGateway {...props} />
      </BladeProvider>
    );
  };

  it('should render SelectGateway without any errors', () => {
    expect(() => <App {...mockProps} />).not.toThrowError();
  });

  it('should render SelectGateway headingText and secondaryText', () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Select Gateway')).toBeInTheDocument();
    expect(screen.getByText('Select a Gateway for your payment provider')).toBeInTheDocument();
  });

  describe('should render list of categorized providers', () => {
    const categorizedProviders = {
      aggregators: {
        'Cards, UPI, Netbanking, EMI, Wallet, and E-Mandate': ['payu'],
        'Cards, Netbanking, Wallet, and UPI': ['ccavenue'],
        'Cards, UPI, Netbanking, and Wallet': ['paytm'],
        'Cards, Netbanking, and UPI': ['billdesk_optimizer'],
        'Cards, UPI, and Netbanking': ['cashfree'],
        'Cards and Netbanking': ['ingenico'],
        'Cards and UPI': ['pinelabs'],
        'Netbanking only': ['atom'],
        // Add more categories and providers here
      },
      international_gateways: {
        'Cards only': ['checkout_dot_com_optimizer'],
        // Add more categories and providers here
      },
      bank_gateways: {
        'Cards only': ['axis_migs', 'cybersource_axis', 'cybersource_hdfc', 'hdfc'],
        'Netbanking only': ['netbanking_axis'],
        'UPI only': ['upi_axis', 'upi_icici', 'upi_mindgate'],
        // Add more categories and providers here
      },
    };

    for (const section in categorizedProviders) {
      if (categorizedProviders.hasOwnProperty(section)) {
        describe(`should render ${GATEWAY_CATEGORIES[section]}`, () => {
          for (const category in categorizedProviders[section]) {
            if (categorizedProviders[section].hasOwnProperty(category)) {
              describe(category, () => {
                test.each(categorizedProviders[section][category])(
                  'should render %s',
                  (provider) => {
                    render(
                      <App
                        {...mockProps}
                        categorizedProviders={{
                          [section]: { [category]: [provider] },
                        }}
                      />,
                    );
                    expect(screen.getByText(GATEWAY_CATEGORIES[section])).toBeInTheDocument();
                    expect(screen.getByText(category)).toBeInTheDocument();
                    expect(screen.getByTestId(provider)).toBeInTheDocument();
                  },
                );
              });
            }
          }
        });
      }
    }
  });

  it('should render No Providers List', () => {
    render(<App {...mockProps} />);
    expect(screen.getByTestId('no-providers-list')).toBeInTheDocument();
  });

  it('should render search input', () => {
    render(<App {...mockProps} />);
    const searchElement = screen.getByPlaceholderText(new RegExp('Search for a gateway', 'i'));
    expect(searchElement).toBeInTheDocument();
  });

  describe('Search Provider', () => {
    const testCases = [
      {
        searchTerm: 'payu',
        expectedTestId: 'payu',
      },
      {
        searchTerm: 'invalid-search-term',
        expectedTestId: 'no-providers-list',
      },
    ];

    testCases.forEach(({ searchTerm, expectedTestId }) => {
      it(`should update search state when input value is changed for search term: "${searchTerm}"`, async () => {
        mockProps.categorizedProviders = {
          aggregators: {
            'Cards only': ['payu'],
            'Netbanking only': ['axis_migs', 'cybersource_axis', 'hdfc'],
            // Add more categories and providers here
          },
          international_gateways: {
            'Cards only': ['checkout_dot_com_optimizer'],
            // Add more categories and providers here
          },
          bank_gateways: {
            'Cards only': ['axis_migs', 'cybersource_axis', 'cybersource_hdfc', 'hdfc'],
            'Netbanking only': ['netbanking_axis'],
            'UPI only': ['upi_axis', 'upi_icici', 'upi_mindgate'],
            // Add more categories and providers here
          },
        };

        render(<App {...mockProps} />);
        const searchElement = screen.getByPlaceholderText(/Search for a gateway/i);
        fireEvent.change(searchElement, { target: { value: searchTerm } });

        await waitFor(() => {
          expect(searchElement).toHaveAttribute('value', searchTerm);
          expect(screen.getByTestId(expectedTestId)).toBeInTheDocument();
        });
      });
    });
  });

  describe('Selected Provider', () => {
    it('should render selected gateway', () => {
      const { getByText, getByTestId, queryByText } = render(
        <App {...mockProps} selectedProvider="payu" />,
      );

      expect(getByText('Gateway')).toBeInTheDocument();
      expect(getByTestId('selected-provider')).toBeInTheDocument();
      expect(queryByText(/Change gateway/)).toBeInTheDocument();
    });

    it('should render selected gateway - readOnly', () => {
      const overRideProps = {
        ...deepClone(mockProps),
        isFormEdit: false,
        selectedProvider: 'payu',
        isEdit: true,
      };

      const { getByText, getByTestId, queryByText } = render(<App {...overRideProps} />);

      expect(getByTestId('selected-provider')).toBeInTheDocument();
      expect(getByText('PayU')).toBeInTheDocument();
      expect(queryByText(/Change gateway/)).not.toBeInTheDocument();
    });
  });
});
