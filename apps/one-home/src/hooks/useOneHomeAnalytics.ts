import { analyticsTrack, getCommonAnalyticsProperties } from '@libs/shared-utils';
import { useQuery, UseQueryResult } from '@tanstack/react-query';
import { useCallback } from 'react';
import fetchSettlementConfig from '../services/api/fetchSettlementConfig';
import { OneHomeAnalytics } from './types';
import { useStore } from '@apps/shell/src/client/store/commonStore';

const evaluateMerchantRisk = (features: Record<string, unknown>) => {
  let merchantRisk = '';
  switch (true) {
    case features?.global_hold_config?.status:
      merchantRisk = 'FOH';
      break;
    case features?.hold?.status:
      merchantRisk = 'SOH';
      break;
    case features?.block?.status:
      merchantRisk = 'Block';
      break;
    default:
      merchantRisk = 'Regular';
  }
  return merchantRisk;
};

const useOneHomeAnalytics = () => {
  const {
    data: settlementConfig,
    isError,
    error,
  }: UseQueryResult = useQuery(['settlement_config'], {
    queryFn: fetchSettlementConfig,
    staleTime: Infinity,
    cacheTime: Infinity,
    refetchOnWindowFocus: false,
    retry: false,
    networkMode: 'always',
  });
  const { user } = useStore((state) => state['session']);
  let merchantProfile: { defaultBU: any; merchantRisk?: string } = {
    defaultBU: user.product
  };

  if (settlementConfig) {
    const { features } = settlementConfig ? settlementConfig?.data?.config : {};
    merchantProfile["merchantRisk"] = evaluateMerchantRisk(features);
  }

  const trackOneHomeAnalytics = useCallback(
    ({ objectName, actionName, properties }: OneHomeAnalytics) => {
      analyticsTrack({
        objectName,
        actionName,
        screen: 'one-home',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
          merchantProfile,
          ...properties,
          experimentName: 'OneHomeV1',
        },
      });
    },
    [merchantProfile],
  );

  return {
    trackOneHomeAnalytics,
    isError,
    error,
  };
};

export default useOneHomeAnalytics;
