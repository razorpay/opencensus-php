import { useToast } from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import { UPDATE_BRAND_MUTATION } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/mutations';

import type {
  Brand,
  BrandPayloadType,
  ModifiedFieldsMapType,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/types';

type BrandUpdateResponse = {
  brandUpdate: {
    code: number;
    success: boolean;
    message: string;
    brand: Brand;
  };
};

const useBrandUpdateMutation = ({
  brandPayload,
  onSuccessHandler,
  modifiedFieldsMap,
  brandId,
  selectedBrandInfo,
}: {
  brandPayload: BrandPayloadType;
  onSuccessHandler: () => void;
  modifiedFieldsMap: ModifiedFieldsMapType;
  brandId: string;
  selectedBrandInfo: Brand;
}) => {
  const toast = useToast();
  const { mutate: updateBrand, isLoading: isUpdateBrandLoading } = useMutation<BrandUpdateResponse>(
    {
      mutationFn: async () => {
        const modifiedFields = Object.keys(modifiedFieldsMap);
        const variables = {
          id: brandId,
        };
        modifiedFields.forEach((field) => {
          if (
            field !== 'name' ||
            (field === 'name' && brandPayload[field] !== selectedBrandInfo[field])
          ) {
            if (typeof brandPayload[field] === 'string') {
              variables[field] = brandPayload?.[field]?.trim();
            } else if (field === 'logo') {
              variables[field] = brandPayload?.documentId;
            } else {
              variables[field] = brandPayload?.[field];
            }
          }
        });
        const response = await graphqlRequestMutation({
          document: UPDATE_BRAND_MUTATION,
          variables,
        });
        return response;
      },
      onSettled: (response) => {
        if (!response?.brandUpdate?.success) {
          toast.show({
            type: 'informational',
            color: 'negative',
            content: response?.brandUpdate?.message,
          });
        } else {
          toast.show({
            type: 'informational',
            color: 'positive',
            content: 'Brand updated successfully',
          });
          onSuccessHandler();
        }
      },
    },
  );

  return { updateBrand, isUpdateBrandLoading };
};

export default useBrandUpdateMutation;
