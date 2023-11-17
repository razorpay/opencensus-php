import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

import ProviderDetails from 'merchant/views/Optimizer/AddProvider/components/ProviderDetails';
import { render, screen } from 'test-utils';

describe('Add Provider > Provider Details', () => {
  let mockProps;

  beforeEach(() => {
    mockProps = {
      isEdit: false,
      isFormEdit: true,
      provider: {
        Provider_name: '',
        Description: '',
      },
      selectedProvider: null,
      validateStep: jest.fn(),
    };
  });

  const App = (props = {}) => {
    return (
      <BladeProvider themeTokens={paymentTheme}>
        <ProviderDetails {...props} />
      </BladeProvider>
    );
  };

  it('should render ProviderDetails without any errors', () => {
    expect(() => <App {...mockProps} />).not.toThrowError();
  });

  it('should render headingText, subText and Next button', () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Provider details')).toBeInTheDocument();
    expect(screen.getByText('Add details of your payment provider')).toBeInTheDocument();
    expect(screen.getByText('Next')).toBeInTheDocument();
  });

  it('should render provider name and description - readOnly', () => {
    mockProps.isFormEdit = false;
    mockProps.provider.Provider_name = 'mock provider';
    mockProps.provider.Description = 'provider description';

    render(<App {...mockProps} />);

    expect(screen.getByText('Provider Name')).toBeInTheDocument();
    expect(screen.getByText('mock provider')).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();
    expect(screen.getByText('provider description')).toBeInTheDocument();
  });
});
