import { merchantFetch } from 'merchant/utils/ajax';
import { ApiResponse, WorkflowConfig } from '../types';

export const getModularOnboardingData = async (
  merchantId: string,
  user_id: string,
): Promise<ApiResponse<WorkflowConfig> | null> => {
  if (!merchantId || !user_id) return null;

  const data = await merchantFetch({
    url: `onboarding/workflow/merchant/${merchantId}`,
    method: 'get',
    data: {
      merchant_id: merchantId,
      account_type: 'merchant',
      user_id,
      product: 'rize_incorporation',
      platform: 'pg',
      country_code: 'IN',
      org_id: 'org_100000razorpay',
    },
  });
  return data;
};

