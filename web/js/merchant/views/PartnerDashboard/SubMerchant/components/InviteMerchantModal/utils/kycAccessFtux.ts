const KEY = 'partnerships-kyc-access-opt-in';

export const getHasSelectedKycAccess = (): boolean | null => {
  const hasSelectedKycAccess = localStorage.getItem(KEY);
  if (!hasSelectedKycAccess) {
    return null;
  }
  const { request_kyc_access } = JSON.parse(hasSelectedKycAccess);

  return request_kyc_access;
};

export const setHasSelectedKycAccess = (request_kyc_access: boolean): void => {
  localStorage.setItem(
    KEY,
    JSON.stringify({
      request_kyc_access,
    }),
  );
};
