import { graphqlRequest } from '@federated/apps/shell/graphql';
import { useQuery, UseQueryResult } from '@tanstack/react-query';
import { WORKFLOW_TYPES } from 'apps/onboarding-experience/src/common/types/merchant';
import {
  MERCHANT_FEATURE_FLAGS,
  MerchantOnboardingDataResponseType,
  MerchantOnboardingDataRequestEnum,
} from 'apps/onboarding-experience/src/common/types/onboarding';
import { MERCHANT_ONBOARDING_DATA_QUERY } from 'apps/onboarding-experience/src/common/queries/onboarding';

type merchantOnboardingDataPropsType = {
  /** The type of workflow to fetch data for (defaults to business website workflow) */
  defaultWorkflow?: WORKFLOW_TYPES;
  /** Array of feature flags to check in the request */
  defaultFeatureFlags?: MERCHANT_FEATURE_FLAGS[];
  /** Optional data sections to fetch - improves performance by requesting only needed data from APIs */
  requestedData?: MerchantOnboardingDataRequestEnum[];
};

/**
 * Custom hook to fetch merchant onboarding data from the GraphQL API
 *
 * This hook retrieves data multiple data points about the merchant
 * including workflow status, feature flags, and verification status.
 */
const useMerchantOnboardingData = ({
  defaultWorkflow,
  defaultFeatureFlags = [],
  requestedData,
}: merchantOnboardingDataPropsType): UseQueryResult<MerchantOnboardingDataResponseType> => {
  return useQuery<MerchantOnboardingDataResponseType>({
    refetchOnWindowFocus: false,
    queryKey: ['merchant_onboarding_data'],
    retry: false,
    // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
    queryFn: () =>
      graphqlRequest({
        document: MERCHANT_ONBOARDING_DATA_QUERY,
        variables: {
          workflow: defaultWorkflow,
          featureFlagNames: defaultFeatureFlags,
          requestedData,
        },
      }),
  });
};

export default useMerchantOnboardingData;
