import React from 'react';

import FAQModal from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/FAQModal';
import { titlesForTracking } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/FAQModal/FAQContent';
import * as analytics from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, userEvent } from 'test-utils';

const trackInviteFlowCommonCtaClickedSpy = jest.spyOn(analytics, 'trackInviteFlowCommonCtaClicked');
const defaultProps = {
  getTriggerComponent: ({ onClick }) => <div onClick={onClick}> Click Me! </div>,
  inviteFlow: 'test',
  productType: PRODUCT_TYPE.PG,
};
describe('FAQModal', () => {
  const renderApp = (props = {}) => {
    render(<FAQModal {...defaultProps} {...props} />);
  };
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should fire correct ARD events', async () => {
    const { inviteFlow, productType } = defaultProps;
    renderApp();
    // Open modal
    await userEvent.click(screen.getByText('Click Me!'));
    expect(screen.getByText('Perform KYC on behalf of your client')).toBeInTheDocument();

    // Click CTAs
    await userEvent.click(screen.getByText(titlesForTracking.WHAT_TO_DO));
    expect(trackInviteFlowCommonCtaClickedSpy).toHaveBeenCalledWith({
      inviteFlow,
      ctaClicked: 'Accordian',
      productType,
      message: titlesForTracking.WHAT_TO_DO,
    });

    await userEvent.click(screen.getByText('Know more about the process'));
    expect(trackInviteFlowCommonCtaClickedSpy).toHaveBeenCalledWith({
      inviteFlow,
      ctaClicked: 'Know more about the process',
      productType,
    });
  });
});
