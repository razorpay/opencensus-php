import { useToast } from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from '@federated/apps/shell/graphql';
import { GET_PRE_SIGNED_URL_MUTATION } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/mutations';
import { verifyGqlErrorResponse } from 'merchant/views/BillMeSettings/common/utils';

const useBrandPreSignedUrlMutation = (errorCallBack: () => void) => {
  const toast = useToast();
  const { mutateAsync: getLogoPreSignedUrl, isLoading: isPreSignedUrlFetchLoading } = useMutation({
    mutationFn: async (filename: string) => {
      const response = await graphqlRequestMutation({
        document: GET_PRE_SIGNED_URL_MUTATION,
        variables: { filename },
      });
      return response;
    },
    onError: (error) => {
      const isBrandUpdateAccessDenied = verifyGqlErrorResponse(error);
      toast.show({
        type: 'informational',
        color: 'negative',
        content: isBrandUpdateAccessDenied
          ? 'Access Denied to upload Brand logo'
          : 'Failed to upload Brand logo',
      });
      errorCallBack();
    },
  });

  return { getLogoPreSignedUrl, isPreSignedUrlFetchLoading };
};

export default useBrandPreSignedUrlMutation;
