import React, { ReactNode, useEffect, useState } from 'react';
import { Spinner } from '@razorpay/blade/components';
import { useLocation } from 'react-router-dom';

import { User } from 'common/typings';
import { SpinnerContainer } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/styles';
import { ReferralData } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/hooks/useReferralLinks';

import ProductWelcomeScreen from './ProductWelcomeScreen';
import { WelcomeScreenContext } from './context';
import useWelcomeScreenCheck, { WelcomeScreenData } from './hooks/useWelcomeScreenCheck';

const initialWelcomeScreenData: WelcomeScreenData = {
  isAdditionalApiCheckLoading: true,
  isAdditionalApiEmpty: true,
  isAcceptedInvitesEmpty: false,
};

type WelcomeScreenContainerProps = {
  productType: string;
  user: User;
  children: ReactNode;
  referralData: ReferralData | undefined;
  isReferralLinksLoading: boolean;
};

const WelcomeScreenContainer = ({
  productType,
  user,
  children,
  referralData,
  isReferralLinksLoading,
}: WelcomeScreenContainerProps): JSX.Element => {
  const location = useLocation();
  const isFilterSearchUsed = location.search !== '';
  const [welcomeScreenData, _setWelcomeScreenData] = useState(initialWelcomeScreenData);

  useEffect(() => {
    _setWelcomeScreenData(initialWelcomeScreenData);
  }, [productType]);

  const setWelcomeScreenData = (nextData) =>
    _setWelcomeScreenData((data) => ({ ...data, ...nextData }));
  const setIsAcceptedInvitesEmpty = (isAcceptedInvitesEmpty) =>
    setWelcomeScreenData({ isAcceptedInvitesEmpty });
  const shouldShowWelcomeScreen = useWelcomeScreenCheck({
    user,
    productType,
    isFilterSearchUsed,
    setWelcomeScreenData,
    welcomeScreenData,
  });

  if (welcomeScreenData.isAdditionalApiCheckLoading)
    return (
      <SpinnerContainer>
        <Spinner testID="welcome-screen-spinner" accessibilityLabel="spinner" size="xlarge" />
      </SpinnerContainer>
    );

  if (isReferralLinksLoading) {
    return (
      <SpinnerContainer>
        <Spinner testID="referral-links-spinner" accessibilityLabel="spinner" size="xlarge" />
      </SpinnerContainer>
    );
  }
  return (
    <WelcomeScreenContext.Provider
      value={{
        isFilterSearchUsed,
        referralData,
        setIsAcceptedInvitesEmpty,
      }}
    >
      {shouldShowWelcomeScreen ? <ProductWelcomeScreen productType={productType} /> : children}
    </WelcomeScreenContext.Provider>
  );
};

export default WelcomeScreenContainer;
