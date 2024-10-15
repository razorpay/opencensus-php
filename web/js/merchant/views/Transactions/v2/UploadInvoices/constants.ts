export const ONBOARDING_PARTNERS = {
  GST_PORTAL: 'gstportal',
  E_INVOICE: 'einvoice',
} as const;

export const ONBOARDING_STATUS = {
  ONBOARDED: 'onboarded',
  EXPIRED: 'expired',
} as const;

export const MODAL_TYPES = {
  UPLOAD_INVOICE: 'UPLOAD_INVOICE',
  ONBOARDING: 'ONBOARDING',
  LOGIN: 'LOGIN',
} as const;

export const REACT_QUERY_CONFIG = {
  cacheTime: 15 * 60 * 1000,
  staleTime: 15 * 60 * 1000,
  retry: false,
  refetchOnWindowFocus: true,
};
