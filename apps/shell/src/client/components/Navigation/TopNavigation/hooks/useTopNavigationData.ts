import { useQuery } from '@tanstack/react-query';
import { useStore } from '@federated/apps/shell/commonStore';
import { fetchUCS } from '@federated/apps/shell/rest-fetch';
import { getProducts } from './utils';
import {
  homeFallbackData,
  paymentsFallbackData,
  partnerFallbackData,
  companyRegistrationFallbackData,
} from './constants';
import errorService from '@razorpay/universe-cli/errorService';
import { DASHBOARD_TEAMS } from '@libs/shared-types';
import { DASHBOARD_PRIORITY_RANKS } from '@libs/shared-types';

export const useTopNavigationData = () => {
  const { user } = useStore((state) => ({ user: state.session.user }));

  const {
    data: actualData,
    isLoading,
    isError,
    error,
  } = useQuery(['topNavData'], {
    queryFn: async () => {
      try {
        const response = await fetchUCS({
          method: 'POST',
          url: `rzp.dashboard.component.v1.ComponentService/GetComponentData`,
          data: {
            alias: 'one_navigation',
          },
        });

        if (!response || response.error || response.components.length === 0) {
          throw new Error('No products data available');
        }
        return response;
      } catch (error) {
        errorService.captureError(error, {
          tags: {
            team: DASHBOARD_TEAMS.CROSS_SELL_EXPERIENCE,
            module: '[@shell]: useTopNavigationData - Top Navigation Data',
          },
          rank: DASHBOARD_PRIORITY_RANKS.P0,
        });
        console.error('Failed to fetch product details for top nav');
        throw new Error('No products data available');
      }
    },
    staleTime: 5 * 60 * 1000, // 5 minutes cache
    refetchOnWindowFocus: false,
    retry: false,
    networkMode: 'always',
  });

  const getFallbackData = ({
    isOneHomeEnabled,
    isPartner,
    isCompanyRegistrationTopNavEnabled,
  }: {
    isOneHomeEnabled: boolean;
    isPartner: boolean;
    isCompanyRegistrationTopNavEnabled: boolean;
  }) => {
    return {
      components: [
        ...(isOneHomeEnabled ? [homeFallbackData] : []),
        paymentsFallbackData,
        ...(isCompanyRegistrationTopNavEnabled ? [companyRegistrationFallbackData] : []),
        ...(isPartner ? [partnerFallbackData] : []),
      ],
    };
  };

  const fallbackData = getFallbackData({
    isOneHomeEnabled: Boolean(window?.IS_ONE_HOME_ENABLED),
    isPartner: Boolean(user?.partner_type),
    isCompanyRegistrationTopNavEnabled: Boolean(window?.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED),
  });

  const products = getProducts({ data: isError ? fallbackData : actualData });

  return {
    products,
    isLoading,
    isError,
    error,
  };
};
