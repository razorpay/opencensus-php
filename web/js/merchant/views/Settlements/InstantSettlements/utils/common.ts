import { PaymentsDashboardUser } from '@libs/shared-types/payments';
import type { ODSConfig } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useODSConfig';

export const CREATE_ODS_ENDPOINT = {
  NEW: 'capital_es/service/instant_settlements/ondemand',
  OLD: 'settlement/ondemand/dashboard',
};

export const ODS_PRICING_ENDPOINT = {
  NEW: 'capital_es/service/instant_settlements/ondemand/fees',
  OLD: 'settlement/ondemand/fees/dashboard',
};

export const SEPERATEDBALANCEURL = 'capital_es/service/balances?type=online_domestic';

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

export const getIsPartialOndemandSettlementEnabled = (user: any) => {
  return user.isOndemandSettlementEnabled && user.isOndemandSettlementsRestricted;
};

export const getIsRouteOndemandSettlementEnabled = (user: any) => {
  return user.isOndemandRouteSettlementsEnabled;
};

export const getIsSamedaySettlementEnabled = (user: any) => {
  return user.isAutomaticSettlementEnabled || user.isAutomaticSettlementRestricted;
};

export function getIsOdsMigrationEnabled(user: PaymentsDashboardUser) {
  return user?.isOdsMigrationEnabled ?? false;
}
