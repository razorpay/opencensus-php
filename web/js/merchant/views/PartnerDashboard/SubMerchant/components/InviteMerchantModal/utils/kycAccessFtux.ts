import { User } from 'common/typings';
import { PARTNER_TYPE } from 'merchant/views/PartnerDashboard/constants';

const KEY = 'partnerships-kyc-access-opt-in';

const getProductKey = (productType: string): string => {
  return `${KEY}-${productType}`;
};

export const getHasSelectedKycAccess = (productType: string, user?: User): boolean | null => {
  const key = getProductKey(productType);
  const hasSelectedKycAccess = window?.localStorage.getItem(key);

  /*----------  KYC Access would be true for Aggregators by default, this condition trumps all other checks  ----------*/
  if (user && user.isPartner?.(PARTNER_TYPE.AGGREGATOR)) return true;

  if (!hasSelectedKycAccess) {
    return null;
  }
  const { request_kyc_access } = JSON.parse(hasSelectedKycAccess);

  return request_kyc_access;
};

export const setHasSelectedKycAccess = (request_kyc_access: boolean, productType: string): void => {
  const key = getProductKey(productType);
  window?.localStorage.setItem(
    key,
    JSON.stringify({
      request_kyc_access,
    }),
  );
};
