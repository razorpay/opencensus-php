import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'common/services/test/test-utils';
import { getUser } from 'merchant/store';
import { referralData } from './mocks/fixtures';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import ReferralBox from 'merchant/views/PartnerDashboard/SubMerchant/ReferralBox';

// TODO : covered only Capital use case, have to cover others later

const tracking = {
  trackEvent: jest.fn(),
};
const closeModal = jest.fn();

const userData = getUser();

const user = {
  ...userData,
  isPartnershipForCapitalEnabled: true,
};
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

  const renderApp = () => {
    return render(
      <ReferralBox
        referralData={referralData}
        product={PRODUCT_TYPE.CAPITAL}
        tracking={tracking}
        closeModal={closeModal}
        partnershipForXEnabled={true}
        user={user}
      />,
    );
  };

  test('should render referralBox with props', () => {
    renderApp();
    expect(screen.getByText('Corporate Credit Card')).toBeInTheDocument();
    expect(screen.getByText('Copy Link')).toBeInTheDocument();
    expect(
      screen.getByText('Refer merchants to Capital products like corporate cards'),
    ).toBeInTheDocument();
  });
});
