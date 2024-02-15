import React from 'react';

import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import CommonWelcomeScreen from './CommonWelcomeScreen';
const POSWelcomeScreen = (): JSX.Element => {
  const getShareReferralLinkVisibility = () => false;
  const getAddMerchantVisibility = () => true;
  return (
    <CommonWelcomeScreen
      getAddMerchantVisibility={getAddMerchantVisibility}
      getShareReferralLinkVisibility={getShareReferralLinkVisibility}
      productType={PRODUCT_TYPE.POS}
      mainTitle="Welcome to Partner POS Dashboard"
    />
  );
};
export default POSWelcomeScreen;
