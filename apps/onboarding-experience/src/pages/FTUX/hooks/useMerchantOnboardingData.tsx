import { graphqlRequest } from '@federated/apps/shell/graphql';
import { useQuery } from '@tanstack/react-query';
import { MERCHANT_ONBOARDING_DATA_QUERY } from '../Queries/dashboard';
import { FEATURE_FLAGS, WORKFLOW_TYPES } from '../types/common';
import { REQUIRED_FEATURE_FLAGS } from '../constants/home';

const useMerchantOnboardingData = ({
  defaultWorkflow = WORKFLOW_TYPES.BUSINESS_WEBSITE,
  defaultFeatureFlags = REQUIRED_FEATURE_FLAGS,
}: {
  defaultWorkflow?: WORKFLOW_TYPES;
  defaultFeatureFlags?: FEATURE_FLAGS[];
}) => {
  return useQuery({
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
        },
      }),
  });
};

export default useMerchantOnboardingData;
