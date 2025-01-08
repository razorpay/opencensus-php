import { merchantFetch } from 'merchant/utils/ajax';

export const patchMerchantPurposeCode = (data: Record<string, string>) =>
  merchantFetch({
    url: 'merchants/purpose/code',
    method: 'patch',
    data,
  });

export const postVKycLink = (
  name: string,
): Promise<{ data?: { details?: { weblink?: string } } }> =>
  merchantFetch({
    url: 'vkyc',
    method: 'POST',
    mode: 'live',
    data: {
      name,
    },
  });
