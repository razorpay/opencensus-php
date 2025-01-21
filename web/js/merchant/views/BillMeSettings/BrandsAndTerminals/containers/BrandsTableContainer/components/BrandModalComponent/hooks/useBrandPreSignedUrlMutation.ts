import { useToast } from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import { GET_PRE_SIGNED_URL_MUTATION } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/mutations';

const useBrandPreSignedUrlMutation = () => {
  const toast = useToast();
  const { mutateAsync: getLogoPreSignedUrl, isLoading: isPreSignedUrlFetchLoading } = useMutation({
    mutationFn: async (filename: string) => {
      const response = await graphqlRequestMutation({
        document: GET_PRE_SIGNED_URL_MUTATION,
        variables: { filename },
      });
      return response;
    },
    onSettled: (response) => {
      if (!response?.storeBrandLogoPreSignedUrl?.success) {
        toast.show({
          type: 'informational',
          color: 'negative',
          content: response?.storeBrandLogoPreSignedUrl?.message,
        });
      }
    },
  });

  return { getLogoPreSignedUrl, isPreSignedUrlFetchLoading };
};

export default useBrandPreSignedUrlMutation;
