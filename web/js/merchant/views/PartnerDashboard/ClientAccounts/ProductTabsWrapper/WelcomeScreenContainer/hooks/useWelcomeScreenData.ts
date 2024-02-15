import { useContext } from 'react';

import {
  WelcomeScreenContext,
  WelcomeScreenContextType,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/WelcomeScreenContainer/context';

const useWelcomeScreenData = (): WelcomeScreenContextType => {
  const { setIsAcceptedInvitesEmpty, referralData, isFilterSearchUsed } =
    useContext(WelcomeScreenContext);
  return {
    setIsAcceptedInvitesEmpty,
    isFilterSearchUsed,
    referralData,
  };
};
export default useWelcomeScreenData;
