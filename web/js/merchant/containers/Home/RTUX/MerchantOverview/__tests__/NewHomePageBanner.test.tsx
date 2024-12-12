import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import SettlementBlockedSOH from '../MerchantOverviewData/Settlement/SettlementBlockedSOH';
import { SOH_MOCK, FOH_MOCK, BLOCK_MOCK, SOH_MOCK_2 } from '../../__tests__/mocks';
import '@testing-library/jest-dom';
import { bladeTheme } from '@razorpay/blade/tokens';
import { useMobile } from 'common/hooks/useMobile';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { BladeProvider } from '@razorpay/blade/components';

const variantOn = {
  settlements_soh_block: { variables: { result: 'on' } },
};

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));
jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(),
}));
jest.mock('react-router-dom', () => ({
  useNavigate: jest.fn(),
}));

describe('SettlementBlockedSOH Component', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockImplementation((breakpoints) => breakpoints === mobileBreakoints);
  });

  test('renders the SOH  status correctly with bank update', () => {
    const props = {
      bankUpdate: true,
      settlementConfig: SOH_MOCK,
    };
    const { container } = render(
      <BladeProvider themeTokens={bladeTheme}>
        <SettlementBlockedSOH {...props} />
      </BladeProvider>,
    );

    expect(
      screen.getByText('Your settlements are on hold, expect an update within 48 hours'),
    ).toBeInTheDocument();
    expect(
      screen.getByText('Once verified, your settlements will be retried.'),
    ).toBeInTheDocument();
  });

  test('renders the SOH  status correctly without bank update', () => {
    const props = {
      bankUpdate: false,
      settlementConfig: SOH_MOCK,
    };
    const { container } = render(
      <BladeProvider themeTokens={bladeTheme}>
        <SettlementBlockedSOH {...props} />
      </BladeProvider>,
    );
    expect(screen.getByText(SOH_MOCK.sub_title)).toBeInTheDocument();
    expect(screen.getByText('Update bank details')).toBeInTheDocument();
  });

  test('renders the SOH  status correctly without bank update and cta_text is not Update Bank Detials', () => {
    const props = {
      bankUpdate: false,
      settlementConfig: SOH_MOCK_2,
    };
    const { container } = render(
      <BladeProvider themeTokens={bladeTheme}>
        <SettlementBlockedSOH {...props} />
      </BladeProvider>,
    );
    expect(screen.getByText(SOH_MOCK_2.sub_title)).toBeInTheDocument();
    expect(screen.getByText('Contact Support')).toBeInTheDocument();
  });

  test('renders the FOH  status correctly', () => {
    const props = {
      bankUpdate: false,
      settlementConfig: FOH_MOCK,
    };
    const { container } = render(
      <BladeProvider themeTokens={bladeTheme}>
        <SettlementBlockedSOH {...props} />
      </BladeProvider>,
    );
    expect(screen.getByText(FOH_MOCK.sub_title)).toBeInTheDocument();

    expect(screen.getByText('Contact Support')).toBeInTheDocument();
  });

  test('renders the Block status correctly', () => {
    const props = {
      bankUpdate: false,
      settlementConfig: BLOCK_MOCK,
    };
    render(
      <BladeProvider themeTokens={bladeTheme}>
        <SettlementBlockedSOH {...props} />
      </BladeProvider>,
    );
    expect(screen.getByText(BLOCK_MOCK.sub_title)).toBeInTheDocument();
    expect(screen.getByText('Contact Support')).toBeInTheDocument();
  });
});
