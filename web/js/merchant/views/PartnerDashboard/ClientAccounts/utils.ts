import { Location } from 'react-router-dom';

import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

// Note: this approach includes submerchant details panels too
export const getProductFromLocation = (location: Location): string => {
  const basePath = '/partners/submerchants';
  const { pathname } = location;
  if (pathname.startsWith(`${basePath}/pos`)) return PRODUCT_TYPE.POS;
  if (pathname.startsWith(`${basePath}/capital`)) return PRODUCT_TYPE.CAPITAL;
  if (pathname.startsWith(`${basePath}/x`)) return PRODUCT_TYPE.X;
  return PRODUCT_TYPE.PG;
};
