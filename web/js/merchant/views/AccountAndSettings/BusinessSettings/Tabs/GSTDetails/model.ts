import { merchantFetch } from 'merchant/utils/ajax';

export const fetchGSTList = (): Promise<{ data?: { results?: string[] } } | undefined> => {
  return merchantFetch('merchant/activation/gst_details');
};

export const submitGSTDetails = (data: {
  gstin: string;
  version: string;
}): Promise<{ data?: { gstin?: string; sync_flow: boolean } } | undefined> => {
  return merchantFetch({
    url: 'merchant/gstin_self_serve',
    data,
    mode: 'live',
    method: 'POST',
  });
};
