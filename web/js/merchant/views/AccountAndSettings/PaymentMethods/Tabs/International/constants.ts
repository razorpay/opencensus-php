export const INSTRUMENT_METHODS = {
  CARD: 'card', // international card
  INTL_BANK_TRANSFER: 'moneysaverexportaccount', // e.g. SWIFT, ACH, SEPA, FPS
  WALLET: 'wallet', // e.g. Paypal
  APPS: 'instantbanktransfer', // alternative payment methods (APMs), local payment methods (LPMs)
} as const;

export const INSTRUMENT_SUB_METHODS = {
  LOCAL_CURRENCY_TRANSFER: 'localcurrencytransfer',
  SWIFT_BANK_TRANSFER: 'swiftbanktransfer',
  INSTANT_BANK_TRANSFER: 'instantbanktransfer',
} as const;
