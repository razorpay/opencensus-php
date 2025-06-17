import { matchPath } from 'react-router-dom';

const MATCH_PATH = '/partners/submerchants/pos/:submerchantId/orders';

export const getSubmerchantIdFromPath = (pathname: string): string | null => {
  const match = matchPath(MATCH_PATH, pathname);
  const { params: { submerchantId = null } = {} } = match || {};
  return submerchantId;
};
