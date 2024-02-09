const KEY = 'partnerships-kyc-access-opt-in';

const getProductKey = (productType: string): string => {
  return `${KEY}-${productType}`;
};

export const getHasSelectedKycAccess = (productType: string): boolean | null => {
  const key = getProductKey(productType);
  const hasSelectedKycAccess = localStorage.getItem(key);
  if (!hasSelectedKycAccess) {
    return null;
  }
  const { request_kyc_access } = JSON.parse(hasSelectedKycAccess);

  return request_kyc_access;
};

export const setHasSelectedKycAccess = (request_kyc_access: boolean, productType: string): void => {
  const key = getProductKey(productType);
  localStorage.setItem(
    key,
    JSON.stringify({
      request_kyc_access,
    }),
  );
};
