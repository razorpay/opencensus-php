import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';

import { ShowNotificationType } from 'common/typings';
import { fetchSubmerchantDetails } from 'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/common/api';
import { PGAcceptedInviteItem } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
type useSubmerchantDetailsArgs = {
  submerchantId: string;
  productType: string;
  showNotification: ShowNotificationType;
};
type useSubmerchantDetailsValue = {
  isLoading: boolean;
  submerchant: PGAcceptedInviteItem | undefined;
};
const useSubmerchantDetails = ({
  submerchantId,
  productType,
  showNotification,
}: useSubmerchantDetailsArgs): useSubmerchantDetailsValue => {
  const [submerchant, setSubmerchant] = useState<PGAcceptedInviteItem | undefined>();
  const { isLoading } = useQuery({
    queryKey: ['get-pos-submerchant-details', submerchantId],
    queryFn: () => fetchSubmerchantDetails(submerchantId, productType),
    refetchOnWindowFocus: false,
    retry: false,
    onSuccess: (response) => {
      setSubmerchant(response.data);
    },
    onError: (err: { errors: Array<string> }) => {
      showNotification?.({
        type: 'error',
        message: err.errors,
      });
    },
  });

  return { isLoading, submerchant };
};

export default useSubmerchantDetails;
