import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent } from 'common/services/test/test-utils';
import { getInitialUserOrgState } from 'common/tests/utils';
import { referralData } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import ShareReferralLink from 'merchant/views/PartnerDashboard/SubMerchant/components/ShareReferralLink';

import * as analytics from 'common/utils/analytics';

const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');

const closeModal = jest.fn();

const session = getInitialUserOrgState({
  isRzpOrg: true,
  userExtra: {
    findTag: jest.fn(),
    isPartnershipForCapitalEnabled: true,
  },
  orgExtra: {},
});

describe('ShareReferralLink', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
  });

  const renderApp = ({ user = session.user, product, ...restProps }) => {
    return render(
      <ShareReferralLink
        closeModal={closeModal}
        referralData={referralData}
        user={user}
        product={product}
        {...restProps}
      />,
    );
  };

  // Note: Skippnig for now: div for Line of Credit is rendering but test matcher is unable to detect it in dom!
  test('should render ShareReferralLink for capital with props', () => {
    renderApp({
      product: PRODUCT_TYPE.CAPITAL,
    });
    expect(screen.getByText('Line of Credit')).toBeInTheDocument();
    expect(screen.getByText(referralData.capital.url)).toBeInTheDocument();
    expect(screen.getByText('Copy Link')).toBeInTheDocument();
    expect(
      screen.getByText('Refer merchants to Capital products like Line of Credit'),
    ).toBeInTheDocument();
  });

  test('should render ShareReferralLink for PG', async () => {
    renderApp({
      product: PRODUCT_TYPE.PG,
    });
    expect(screen.getByText('Razorpay Payments')).toBeInTheDocument();
    await userEvent.click(screen.getByText('No, my client will perform KYC on their own'));
    expect(screen.getByText(referralData.primary.url)).toBeInTheDocument();
    await userEvent.click(screen.getByText('Yes, I will assist my client with their KYC'));
    expect(screen.getByText(referralData.primary.easy_kyc_access_url)).toBeInTheDocument();
  });

  test('track events in ShareReferralLink for PG', async () => {
    renderApp({
      product: PRODUCT_TYPE.PG,
    });
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Social Share Referral Box',
        actionName: 'Opened',
      }),
    );
    await userEvent.click(screen.getByLabelText('Close'));
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Social Share Referral Box',
        actionName: 'Closed',
      }),
    );
  });
});
