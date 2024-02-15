import React from 'react';

import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

import CommonWelcomeScreen from './CommonWelcomeScreen';
const PGWelcomeScreen = (): JSX.Element => {
  const { isPlatformPartnerInviteFlowEnabled } = usePartnerDashboardExperiments();

  const getShareReferralLinkVisibility = (currentUser) => currentUser.isPartner('reseller');
  const getAddMerchantVisibility = (currentUser) =>
    isPlatformPartnerInviteFlowEnabled || !currentUser.isPartner('pure_platform');

  return (
    <CommonWelcomeScreen
      getAddMerchantVisibility={getAddMerchantVisibility}
      getShareReferralLinkVisibility={getShareReferralLinkVisibility}
      productType={PRODUCT_TYPE.PG}
    />
  );
};
export default PGWelcomeScreen;
