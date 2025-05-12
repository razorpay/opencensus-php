import React from 'react';
import { render, screen, userEvent, renderWithSuspense } from 'test-utils';
import SwitchAccount from '../components/SwitchAccount';
import { useMultiAccount } from 'merchant/views/CompanyRegistration/hooks/SwitchAccountHook';
import {
  trackEventOnIncorporationCompletePageView,
  trackEventOnIncorporationCompleteLinkClick,
} from '../analytics';

jest.mock('@libs/shared-utils', () => ({
  useMobile: jest.fn(),
}));

jest.mock('merchant/views/CompanyRegistration/hooks/SwitchAccountHook', () => ({
  useMultiAccount: jest.fn(),
}));

jest.mock('../analytics', () => ({
  trackEventOnIncorporationCompletePageView: jest.fn(),
  trackEventOnIncorporationCompleteLinkClick: jest.fn(),
}));

// Lazy-loaded components are mocked to avoid actually importing them
jest.mock('../components/AccountModal', () => ({
  __esModule: true,
  default: () => <div>Mocked Modal</div>,
}));

jest.mock('../components/AccountBottomSheet', () => ({
  __esModule: true,
  default: () => <div>Mocked BottomSheet</div>,
}));

describe('SwitchAccount Component', () => {
  beforeEach(() => {
    (useMultiAccount as jest.Mock).mockReturnValue({
      selected: 'abc',
      setSelected: jest.fn(),
      isLoading: false,
      handleUserAction: jest.fn(),
    });
  });

  test('renders static content and CTA correctly', () => {
    render(<SwitchAccount />);

    expect(
      screen.getByText(/you’re now eligible to upgrade to a registered razorpay business account/i),
    ).toBeInTheDocument();

    expect(
      screen.getByText(/As a registered entity, you can now create a registered business account/i),
    ).toBeInTheDocument();

    expect(screen.getByRole('button', { name: /Create Account/i })).toBeInTheDocument();
  });

  test('tracks page view on mount', () => {
    render(<SwitchAccount />);
    expect(trackEventOnIncorporationCompletePageView).toHaveBeenCalledTimes(1);
  });

  test('calls link click tracking when CTA is clicked', async () => {
    renderWithSuspense(<SwitchAccount />);
    await userEvent.click(screen.getByRole('button', { name: /Create Account/i }));
    expect(trackEventOnIncorporationCompleteLinkClick).toHaveBeenCalledTimes(1);
  });
});
