import { useEffect } from 'react';

import { User } from 'common/typings';
import { checkIfAllInvitesEmpty } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/PaymentsClients/AllInvites/api';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

const checkAdditionalApiEmpty = async ({ experiments, partnerId, productType }) => {
  switch (productType) {
    case PRODUCT_TYPE.POS:
    case PRODUCT_TYPE.PG: {
      const { isPartnershipsInviteFlowEnabled, isPlatformPartnerInviteFlowEnabled } = experiments;

      // Note: productType is implicit here.
      const isInviteFlow = isPartnershipsInviteFlowEnabled || isPlatformPartnerInviteFlowEnabled;

      if (isInviteFlow) {
        const isPGInvitesEmpty = await checkIfAllInvitesEmpty(partnerId, productType);
        return isPGInvitesEmpty;
      } else {
        // bypass check for legacy flow
        return true;
      }
    }
    case PRODUCT_TYPE.CAPITAL:
    case PRODUCT_TYPE.X:
    default:
      return true;
  }
};
export type WelcomeScreenData = {
  isAdditionalApiCheckLoading: boolean;
  isAdditionalApiEmpty: boolean;
  isAcceptedInvitesEmpty: boolean;
};
type useWelcomeScreenCheckArgs = {
  user: User;
  productType: string;
  setWelcomeScreenData: (args: Partial<WelcomeScreenData>) => void;
  isFilterSearchUsed: boolean;
  welcomeScreenData: WelcomeScreenData;
};
const useWelcomeScreenCheck = ({
  user,
  productType,
  setWelcomeScreenData,
  isFilterSearchUsed,
  welcomeScreenData,
}: useWelcomeScreenCheckArgs): boolean => {
  const experiments = usePartnerDashboardExperiments();
  const { isAdditionalApiEmpty } = welcomeScreenData;
  useEffect(() => {
    if (user.id) {
      setWelcomeScreenData({ isAdditionalApiCheckLoading: true });
      checkAdditionalApiEmpty({ experiments, partnerId: user.id, productType })
        .then((nextValue) => {
          setWelcomeScreenData({
            isAdditionalApiEmpty: nextValue,
            isAdditionalApiCheckLoading: false,
          });
        })
        .catch(() => {
          setWelcomeScreenData({
            isAdditionalApiEmpty: false,
            isAdditionalApiCheckLoading: false,
          });
        });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [productType, user.id]);
  const { isAcceptedInvitesEmpty, isAdditionalApiCheckLoading } = welcomeScreenData;
  const shouldShowWelcomeScreen =
    !isAdditionalApiCheckLoading &&
    isAcceptedInvitesEmpty &&
    !isFilterSearchUsed &&
    isAdditionalApiEmpty;

  return shouldShowWelcomeScreen;
};
export default useWelcomeScreenCheck;
