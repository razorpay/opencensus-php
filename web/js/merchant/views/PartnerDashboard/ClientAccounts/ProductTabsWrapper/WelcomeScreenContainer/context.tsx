import { createContext } from 'react';

import { ReferralData } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/hooks/useReferralLinks';

export type WelcomeScreenContextType = {
  isFilterSearchUsed: boolean;
  referralData: ReferralData | undefined;
  setIsAcceptedInvitesEmpty: (args: boolean) => void;
};

const initialState = {
  isFilterSearchUsed: true,
  referralData: undefined,
  setIsAcceptedInvitesEmpty: () => {},
};
export const WelcomeScreenContext = createContext<WelcomeScreenContextType>(initialState);
