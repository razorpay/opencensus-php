import {
  getOdsConfigHandler,
  getPgBalanceHandler,
  getPricingBreakupHandler,
  getOdsValidateHandler,
  getPostODSHandler,
  getLinkedAccountBalanceHandler,
  getPostRouteODSHandler,
} from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/handlers';

export const odsConfigGlobalLimitBreachedHandler = getOdsConfigHandler({
  disable: true,
  blocked: false,
});

export const odsConfigGlobalLimitBreachedWithLimitHandler = getOdsConfigHandler({
  disable: true,
  blocked: false,
  available_limit: 90,
  max_limit: 10000,
});

export const odsConfigMerchantLimitBreachedHandler = getOdsConfigHandler({
  disable: true,
  blocked: false,
  available_limit: 0,
  max_limit: 10000,
});

export const odsConfigNoBreachHandler = getOdsConfigHandler({
  disable: false,
  blocked: false,
});

export const odsConfigBlockedHandler = getOdsConfigHandler({
  disable: false,
  blocked: true,
});

export const odsConfigNoBreachWithLimitHandler = getOdsConfigHandler({
  disable: false,
  blocked: false,
  available_limit: 299000,
  max_limit: 3980000,
});

export const odsConfigErrorHandler = getOdsConfigHandler(null, false);

export const pgBalanceHandler = getPgBalanceHandler();

export const pricingBreakupHandler = getPricingBreakupHandler();

export const odsRestrictedConfigHandler = getOdsValidateHandler({
  attempts_left: 1000,
  settlable_amount: 50000,
  max_amount_limit: 500000,
});

export const postODSHandler = getPostODSHandler();

export const routeBalanceHandler = getLinkedAccountBalanceHandler();
export const postRouteODSHandler = getPostRouteODSHandler();
