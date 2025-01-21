import { useToast } from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import { CREATE_BRAND_MUTATION } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/mutations';

import type {
  Brand,
  BrandPayloadType,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/types';

type BrandCreateResponse = {
  storeBrandCreate: {
    code: number;
    success: boolean;
    message: string;
    brand: Brand;
  };
};

const useBrandCreateMutation = ({
  brandPayload,
  onSuccessHandler,
}: {
  brandPayload: BrandPayloadType;
  onSuccessHandler: () => void;
}) => {
  const toast = useToast();
  const { mutate: createBrand, isLoading: isCreateBrandLoading } = useMutation<BrandCreateResponse>(
    {
      mutationFn: async () => {
        const response = await graphqlRequestMutation({
          document: CREATE_BRAND_MUTATION,
          variables: {
            name: brandPayload?.name?.trim(),
            description: brandPayload?.description?.trim(),
            logo: brandPayload?.documentId,
          },
        });
        return response;
      },
      onSettled: (response) => {
        if (!response?.storeBrandCreate?.success) {
          toast.show({
            type: 'informational',
            color: 'negative',
            content: response?.storeBrandCreate?.message,
          });
        } else {
          toast.show({
            type: 'informational',
            color: 'positive',
            content: 'Brand created successfully',
          });
          onSuccessHandler();
        }
      },
    },
  );

  return { createBrand, isCreateBrandLoading };
};

export default useBrandCreateMutation;
