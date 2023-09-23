import { ACTION_QUERY_PARAM_KEY } from 'merchant/views/Account/Profile/deeplink-constants';

// TODO: Update this functionality after React router v6 upgrade using useSearchParams
// If used current v4 for this then this will be a blocker for upgrade pr

export const removeQueryParam = (): void => {
  const url = new URL(window.location.href);
  const triggerFPRAction = url.searchParams.get(ACTION_QUERY_PARAM_KEY);
  if (triggerFPRAction) {
    url.searchParams.delete(ACTION_QUERY_PARAM_KEY);
    history.replaceState(history.state, '', url.href);
  }
};

export const getSanitizeValue = (value: string): string => value.replace(/[,]/g, '');
