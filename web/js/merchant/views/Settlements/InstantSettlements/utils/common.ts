import type { ODSConfig } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useODSConfig';

export const getHasMerchantLevelLimit = (
  odsAvailableLimit: ODSConfig['available_limit'],
): boolean => {
  return odsAvailableLimit !== null && odsAvailableLimit !== undefined;
};

export const getIsMerchantLimitBreached = (odsConfig?: ODSConfig) => {
  return (
    !!odsConfig?.disable &&
    getHasMerchantLevelLimit(odsConfig?.available_limit) &&
    Number(odsConfig?.available_limit || 0) <= 0
  );
};

export const getIsGlobalLimitBreached = (odsConfig?: ODSConfig) => {
  return !!odsConfig?.disable && !getIsMerchantLimitBreached(odsConfig);
};
