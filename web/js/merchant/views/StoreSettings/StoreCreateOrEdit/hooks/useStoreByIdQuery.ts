import { graphqlRequest } from 'common/services/graphql/graphql-client';
import { useNavigate } from 'react-router-dom';
import { STORE_BY_ID } from 'merchant/views/StoreSettings/StoreCreateOrEdit/queries';
import { useQuery } from '@tanstack/react-query';
import { useToast } from '@razorpay/blade/components';

const useStoreByIdQuery = ({ id, currentStep }) => {
  const navigate = useNavigate();
  const toast = useToast();

  const {
    data: storeData,
    isLoading: isGetStoreLoading,
    isRefetching: isGetStoreRefetching,
  } = useQuery({
    queryKey: ['store', id, currentStep],
    queryFn: async () => graphqlRequest({ document: STORE_BY_ID, variables: { id } }),
    refetchOnWindowFocus: false,
    enabled: !!id,
    onSettled: (response) => {
      if (response?.errors) {
        toast.show({
          type: 'informational',
          color: 'negative',
          content: "Couldn't fetch store details",
        });
      }
      if (response?.storeById.dates?.deletedAt) {
        navigate(`/store-settings/stores-list/${id}`);
      }
    },
  });
  return {
    storeData,
    isGetStoreLoading,
    isGetStoreRefetching,
  };
};

export default useStoreByIdQuery;
