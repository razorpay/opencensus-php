import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent } from 'common/services/test/test-utils';
import { getInitialUserOrgState } from 'common/tests/utils';
import * as analytics from 'common/utils/analytics';
import { referralData } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import ShareReferralLink from 'merchant/views/PartnerDashboard/SubMerchant/components/ShareReferralLink';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

const analyticsTrackWithUserInfoSpy = jest.spyOn(analytics, 'analyticsTrackWithUserInfo');
const defaultUserExtra = {
  findTag: jest.fn(),
  isPartner: (partner_type = 'reseller') => partner_type === 'reseller',
  isPartnershipForCapitalEnabled: true,
  isPartnerAgentRole: false,
};
const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: true,
  isPartnershipsForPosEnabled: true,
  isPlatformPartnerInviteFlowEnabled: false,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

describe('ShareReferralLink', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
    window.open = jest.fn();
  });

  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });

  const renderApp = ({ userExtra = {}, orgExtra = {} } = {}, props = {}, experiments = {}) => {
    mockPartnerDashboardExperiments = {
      ...defaultPartnerDashboardExperiments,
      ...experiments,
    };
    const session = getInitialUserOrgState({
      isRzpOrg: true,
      userExtra: {
        ...defaultUserExtra,
        ...userExtra,
      },
      orgExtra,
    });
    return render(<ShareReferralLink referralData={referralData} {...props} />, {
      initialState: {
        session,
      },
    });
  };

  test('should render ShareReferralLink correctly for capital', () => {
    renderApp(
      {},
      {
        initialProductType: PRODUCT_TYPE.CAPITAL,
      },
    );
    expect(screen.getByText('Line of Credit')).toBeInTheDocument();
    expect(screen.getByText(referralData.capital.url)).toBeInTheDocument();
    expect(screen.getByText('Copy Link')).toBeInTheDocument();
    expect(
      screen.getByText('Refer merchants to Capital products like Line of Credit'),
    ).toBeInTheDocument();
  });

  test('should render ShareReferralLink correctly for X', () => {
    renderApp(
      {},
      {
        initialProductType: PRODUCT_TYPE.X,
      },
      { isPartnershipsForPosEnabled: false },
    );
    expect(screen.getByText('RazorpayX')).toBeInTheDocument();
    expect(screen.getByText(referralData.banking.url)).toBeInTheDocument();
    expect(
      screen.getByText(
        'Refer merchants to RazorpayX products like Current account to process payouts',
      ),
    ).toBeInTheDocument();
  });

  test('should render ShareReferralLink correctly for payments', async () => {
    renderApp(
      {},
      {
        initialProductType: PRODUCT_TYPE.PG,
      },
      { isPartnershipsForPosEnabled: false },
    );
    expect(screen.getByText('Razorpay Payments')).toBeInTheDocument();
    await userEvent.click(screen.getByText('No, my client will perform KYC on their own'));
    expect(screen.getByText(referralData.primary.url)).toBeInTheDocument();
    await userEvent.click(screen.getByText('Yes, I will assist my client with their KYC'));
    expect(screen.getByText(referralData.primary.easy_kyc_access_url)).toBeInTheDocument();
  });

  test('track events in ShareReferralLink for payments', async () => {
    renderApp(
      {},
      {
        initialProductType: PRODUCT_TYPE.PG,
      },
    );
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Social Share Referral Box',
        actionName: 'Opened',
      }),
    );
    await userEvent.click(screen.getByText('Yes, I will assist my client with their KYC'));
    await userEvent.click(screen.getByText('Copy Link'));

    expect(analyticsTrackWithUserInfoSpy).toHaveBeenNthCalledWith(
      2,
      expect.objectContaining({
        objectName: 'Copy Referal Link',
        actionName: 'Clicked',
        properties: {
          inviteFlow: 'SHARE_REFERRAL_LINK',
          productType: 'primary',
          isKycAssistedSelected: true,
        },
      }),
    );
    await userEvent.click(screen.getByLabelText('Close'));

    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Social Share Referral Box',
        actionName: 'Closed',
      }),
    );
  });

  test('should render ShareReferralLink correctly for POS', async () => {
    renderApp(
      {},
      {
        initialProductType: PRODUCT_TYPE.POS,
      },
    );
    expect(screen.getByText('Razorpay POS')).toBeInTheDocument();
    await userEvent.click(screen.getByText('No, my client will perform KYC on their own'));
    expect(screen.getByText(referralData.pos.url)).toBeInTheDocument();
    await userEvent.click(screen.getByText('Yes, I will assist my client with their KYC'));
    expect(screen.getByText(referralData.pos.easy_kyc_access_url)).toBeInTheDocument();
  });

  test('track events in ShareReferralLink for POS', async () => {
    renderApp(
      {},
      {
        initialProductType: PRODUCT_TYPE.POS,
      },
    );
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Social Share Referral Box',
        actionName: 'Opened',
      }),
    );
    await userEvent.click(screen.getByText('Yes, I will assist my client with their KYC'));
    await userEvent.click(screen.getByAltText('share via fb'));

    expect(analyticsTrackWithUserInfoSpy).toHaveBeenNthCalledWith(
      2,
      expect.objectContaining({
        objectName: 'Social Share Referral Link',
        actionName: 'Clicked',
        properties: {
          inviteFlow: 'SHARE_REFERRAL_LINK',
          isKycAssistedSelected: true,
          productType: PRODUCT_TYPE.POS,
          socialMedia: 'fb',
        },
      }),
    );
    await userEvent.click(screen.getByLabelText('Close'));

    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Social Share Referral Box',
        actionName: 'Closed',
      }),
    );
  });
  test('should render only POS if isPartnerAgentRole is true and the feature is enabled', () => {
    renderApp(
      { userExtra: { isPartnerAgentRole: true } },
      {
        initialProductType: PRODUCT_TYPE.POS,
      },
      {
        isPartnershipsInviteFlowEnabled: true,
        isPartnershipsForPosEnabled: true,
      },
    );
    expect(screen.queryByText('Razorpay Payments')).not.toBeInTheDocument();
    expect(screen.queryByText('Line of Credit')).not.toBeInTheDocument();
    expect(screen.queryByText('RazorpayX')).not.toBeInTheDocument();
    expect(screen.getByText('Razorpay POS')).toBeInTheDocument();
  });
});
