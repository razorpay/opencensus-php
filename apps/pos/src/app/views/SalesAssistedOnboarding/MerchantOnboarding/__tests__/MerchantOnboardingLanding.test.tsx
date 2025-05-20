import React from 'react';
import { render, screen } from 'apps/pos/src/services/test/test-utils';
import { MerchantOnboardingLanding } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/MerchantOnboardingLanding';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { getMockUseOnboardingContext } from './mocks/fixtures';
import { FileIcon } from '@razorpay/blade/components';

const renderApp = () => {
  render(<MerchantOnboardingLanding />);
};
jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext',
  () => {
    return { __esModule: true, default: jest.fn() };
  },
);

describe('<MerchantOnboardingLanding/>', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });
  test('should render merchant onboarding landing page', async () => {
    (useOnboardingContext as jest.Mock).mockReturnValueOnce({
      ...getMockUseOnboardingContext(<FileIcon />),
    });
    renderApp();
    expect(screen.getByText(/Step 2 of 6 completed/i)).toBeInTheDocument();
    expect(screen.getByText(/Adding a new merchant/i)).toBeInTheDocument();
    expect(screen.getByText(/Merchant KYC/i)).toBeInTheDocument();
    expect(screen.getByText(/Device selection & ordering/i)).toBeInTheDocument();
    expect(screen.getByText(/ Payment Methods & Service Selection/i)).toBeInTheDocument();
    expect(screen.getByText(/Additional details/i)).toBeInTheDocument();
    expect(screen.getByText(/Agreement Signing/i)).toBeInTheDocument();
  });
});
