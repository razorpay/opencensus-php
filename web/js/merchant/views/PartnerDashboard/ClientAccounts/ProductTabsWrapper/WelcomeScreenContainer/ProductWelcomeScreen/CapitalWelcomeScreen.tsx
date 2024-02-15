import React from 'react';

import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import CommonWelcomeScreen from './CommonWelcomeScreen';
const CapitalWelcomeScreen = (): JSX.Element => {
  const getShareReferralLinkVisibility = (currentUser) => currentUser.isPartner('reseller');
  const getAddMerchantVisibility = (currentUser) => !currentUser.isPartner('pure_platform');

  return (
    <CommonWelcomeScreen
      getAddMerchantVisibility={getAddMerchantVisibility}
      getShareReferralLinkVisibility={getShareReferralLinkVisibility}
      productType={PRODUCT_TYPE.CAPITAL}
    />
  );
};
export default CapitalWelcomeScreen;
