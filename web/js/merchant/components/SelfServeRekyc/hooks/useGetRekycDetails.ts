import { useQuery } from '@tanstack/react-query';

import {RekycDetailsApiData} from 'merchant/components/SelfServeRekyc/types';
import { merchantFetch } from '@dashboards/payments/utils/merchantFetch';
import { getMode } from '@federated/apps/shell/commonStore';

let hasRekycDetailsFetched = false;

const getRekycData = async (merchantId: string, showCurrentData: boolean): Promise<RekycDetailsApiData | Record<string, never>> => {
  try {
    const response = await merchantFetch<Record<'data', RekycDetailsApiData>>({
      method: 'POST',
      absUrl: '/mes/rzp.merchant_experience_service.kyc_details.v1.KycDetailsService/GetKycDetails',
      headers: {
        'X-Razorpay-Mode': getMode()
      },
      data:{
        kyc_type: 'rekyc',
        merchant_id: merchantId,
        is_current: showCurrentData,
      }
    });

    return response?.data ? response.data?.[0] : {};
  } catch (e: any) {
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const useGetRekycDetails = ({merchantId, showCurrentData}: {merchantId: string, showCurrentData: boolean}) =>
  useQuery<RekycDetailsApiData | Record<string, never>>(['RekycDetails'], () => getRekycData(merchantId, showCurrentData), {
    enabled: !hasRekycDetailsFetched,
    refetchOnWindowFocus: false,
    refetchOnMount: true,
    retry: 0,
    cacheTime: 0,
    onSuccess: () => {
      hasRekycDetailsFetched = true;
    },
  });
