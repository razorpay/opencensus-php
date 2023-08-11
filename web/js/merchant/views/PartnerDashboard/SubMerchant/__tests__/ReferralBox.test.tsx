import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent } from 'common/services/test/test-utils';
import { referralData } from './mocks/fixtures';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import ReferralBox from 'merchant/views/PartnerDashboard/SubMerchant/ReferralBox';
import { getInitialUserOrgState } from 'common/tests/utils';
// TODO : covered only Capital use case, have to cover others later
import * as analytics from 'common/utils/analytics';

const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');

const tracking = {
  trackEvent: jest.fn(),
};
const closeModal = jest.fn();

const session = getInitialUserOrgState({
  isRzpOrg: true,
  userExtra: {
    isPartnershipForCapitalEnabled: true,
  },
  orgExtra: {},
});

describe('ReferralBox', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
    window.rzp_user = {};
    window.rzpQ = {
      merchantActions: () => {
        return {
          initiated: jest.fn(),
        };
      },
      onbr: () => {
        return {
          interaction: jest.fn(),
        };
      },
    };

    window.rzpQ.component = jest.fn();
  });

  const renderApp = ({ user = session.user, ...restProps }) => {
    return render(
      <ReferralBox
        closeModal={closeModal}
        partnershipForXEnabled={true}
        referralData={referralData}
        tracking={tracking}
        user={user}
        {...restProps}
      />,
    );
  };

  test('should render referralBox for capital with props', () => {
    renderApp({
      product: PRODUCT_TYPE.CAPITAL,
      user: { ...session.user, partnershipForXEnabled: true },
    });
    expect(screen.getByText('Line Of Credit')).toBeInTheDocument();
    expect(screen.getByText(referralData.capital.url)).toBeInTheDocument();
    expect(screen.getByText('Copy Link')).toBeInTheDocument();
    expect(
      screen.getByText('Refer merchants to Capital products like Line Of Credit'),
    ).toBeInTheDocument();
  });

  test('should render referralBox for PG', () => {
    renderApp({
      product: PRODUCT_TYPE.PG,
      user: { ...session.user, partnershipForXEnabled: true },
    });
    expect(screen.getByText('Razorpay Payments')).toBeInTheDocument();
    expect(screen.getByText(referralData.primary.url)).toBeInTheDocument();
  });

  test('track events in referralBox for PG', async () => {
    renderApp({
      product: PRODUCT_TYPE.PG,
      user: { ...session.user, partnershipForXEnabled: true },
    });
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Social Share Referral Box',
        actionName: 'Opened',
      }),
    );
    await userEvent.click(screen.getByTestId('modal-header-close-btn'));
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Social Share Referral Box',
        actionName: 'Closed',
      }),
    );
  });
});
