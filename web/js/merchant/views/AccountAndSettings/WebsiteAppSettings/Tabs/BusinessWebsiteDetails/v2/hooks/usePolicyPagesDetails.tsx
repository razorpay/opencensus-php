import { useMutation } from '@tanstack/react-query';

import { fetch } from '@federated/apps/shell/rest-fetch';

import { PolicyPagesDetails, WebsitePolicyPagesCreationPayload } from '../types';
import { WEBSITE_UPDATE_API_BASE_URL } from '../utils';

// const getPolicyPagesDetails = async (data): Promise<PolicyPagesDetails> => {
//   try {
//     return await fetch<PolicyPagesDetails>({
//       method: 'POST',
//       url: `${WEBSITE_UPDATE_API_BASE_URL}/GetMerchantPolicyComplianceDetails`,
//       data,
//     });
//   } catch (e: any) {
//     throw new Error(e?.response?.errors?.[0]);
//   }
// };

const saveMerchantPolicyPagesDetails = async (data): Promise<PolicyPagesDetails> => {
  try {
    return await fetch<PolicyPagesDetails>({
      method: 'POST',
      url: `${WEBSITE_UPDATE_API_BASE_URL}/SaveMerchantPolicyComplianceDetails`,
      data,
    });
  } catch (e: any) {
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const usePolicyPagesDetails = () => {
  // const query = useQuery(['getPolicyPageDetails'], () => getPolicyPagesDetails(mode), {
  //   refetchOnWindowFocus: false,
  //   refetchOnMount: true,
  //   retry: 0,
  //   onSuccess,
  // });

  const mutation = useMutation<PolicyPagesDetails, unknown, WebsitePolicyPagesCreationPayload>({
    mutationFn: (payload) => saveMerchantPolicyPagesDetails(payload),
    onSuccess: () => {},
    onError: () => {},
  });

  return {
    // data: query.data,
    // isFetching: query.isFetching,
    // error: query.error,
    mutate: mutation.mutateAsync,
    isPosting: mutation.isLoading,
    postError: mutation.error,
  };
};
