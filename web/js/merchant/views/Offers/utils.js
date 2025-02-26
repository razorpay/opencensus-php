import rolesList from 'merchant/helpers/permissions/roles-list';
import { PAYMENT_NETWORK_MAP, ISSUERS, CARD_TYPES } from 'merchant/views/Offers/constants';
import { isExperimentEnabled } from 'common/splitz/utils';

const NetworksAndIssuers = { ...PAYMENT_NETWORK_MAP, ...ISSUERS };

const { ADMIN, OWNER, SELLERAPP } = rolesList;
export const isOfferIdClickable = (user) => [ADMIN, OWNER, SELLERAPP].includes(user.role);

/**
 * Utility function to check if the passed issuer is a debit card
 * @param {string} issuer
 * @returns {boolean} If the issuer is debit card or not
 */
export const isDebitCardIssuer = (issuer) => {
  try {
    return issuer.endsWith('_DC');
  } catch {
    return false;
  }
};

/**
 *
 * Function which returns a label for the given issuer
 * @param {string} issuer
 * @returns The label for the issuer
 */
export const getIssuerLabel = (issuer) => {
  // Check if the issuer is debit card
  const isDebitCard = isDebitCardIssuer(issuer);
  if (isDebitCard) issuer = issuer.slice(0, -3); // Removing '_DC'
  const label = NetworksAndIssuers[issuer] || issuer;

  return isDebitCard ? `${label} Debit Card` : label;
};

/**
 *
 * Updates the offer data to a new format that incorporates backend changes. This includes
 * removing any trailing characters from the issuer string and setting the payment method
 * type to 'debit' if the issuer is a debit card issuer.
 */
export const updateOfferDataFormat = (offerData) => {
  const modifiedOfferData = { ...offerData };

  // If the issuer is a debit card issuer, remove '_DC' from the end of the issuer string
  if (isDebitCardIssuer(modifiedOfferData?.issuer)) {
    modifiedOfferData.issuer = modifiedOfferData?.issuer?.slice?.(0, -3);
    modifiedOfferData.payment_method_type = CARD_TYPES.DEBIT;
  }

  return modifiedOfferData;
};

export const isGranularOfferExperimentEnabled = (splitz) => {
  const upi_granular_offer_dashboard =
    splitz?.abExperiments?.upi_granular_offer_dashboard || undefined;
  return isExperimentEnabled(upi_granular_offer_dashboard);
};

export const getIs10DigitBinExperimentEnabled = (splitz) => {
  return isExperimentEnabled(splitz?.abExperiments?.OE_FETCH_IIN_FROM_BIN_EXP);
};
