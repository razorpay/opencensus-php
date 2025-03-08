import { isMobile } from 'common/utils/validators';
import moment from 'moment';

import {
  APPLICATION_STATE_SEQUENCE,
  CAPITAL_PRODUCT_CODES,
  ERROR_STATES,
} from 'merchant/views/Capital/Loans/constants';
import * as LocalStorageService from 'common/utils/localStorage';
import {
  COLLECTIONS_PRODUCT_TYPES,
  CASH_ADVANCE_PRODUCT_TYPES,
  CASH_ON_CARD_RENDER_DATE_KEY,
} from 'merchant/views/Capital/CashAdvance/constants';
import api from 'merchant/views/Capital/Loans/LoansCollections/api';
import store from 'merchant/store';

export const calculatePercentageAmount = (rateInBPS, credit_amount) => {
  return ((parseInt(rateInBPS, 10) / 100) * parseInt(credit_amount, 10)) / 100;
};

export const getDisbursalAmount = (creditOffered, processingFeePercentage, taxPercentage) => {
  const processingFee = calculatePercentageAmount(processingFeePercentage, creditOffered);
  const taxAmount = calculatePercentageAmount(taxPercentage, processingFee);
  return creditOffered - processingFee - taxAmount;
};

export const isPreceedingState = (currentState, activeState, strict = false) => {
  const currentStateIndex = APPLICATION_STATE_SEQUENCE.indexOf(currentState);
  const activeStateIndex = APPLICATION_STATE_SEQUENCE.indexOf(activeState);
  return strict ? currentStateIndex < activeStateIndex : currentStateIndex <= activeStateIndex;
};

export function postToUrl(path, params, method = 'post') {
  const form = document.createElement('form');
  form.method = method;
  form.action = path;

  for (const key in params) {
    if (params.hasOwnProperty(key)) {
      const hiddenField = document.createElement('input');
      hiddenField.type = 'hidden';
      hiddenField.name = key;
      hiddenField.value = params[key];

      form.appendChild(hiddenField);
    }
  }

  document.body.appendChild(form);
  form.submit();
}

export const isLoanProduct = (productName) => productName === CAPITAL_PRODUCT_CODES.LOAN;

export const isCashAdvanceProduct = (productName) =>
  productName === CAPITAL_PRODUCT_CODES.CASH_ADVANCE;

export const isLOCEMIProduct = (productCode) => productCode === CAPITAL_PRODUCT_CODES.LOC_EMI;

export const getApplicationSteps = (applicationStateGroups) => {
  return Object.entries(applicationStateGroups).reduce(
    (acc, [_, states]) => [...acc, ...states],
    [],
  );
};

const getNonFailedSteps = (applicationSteps) => {
  return applicationSteps.filter((state) => !ERROR_STATES.includes(state));
};

export const getStepIndex = (step, applicationStateGroups) => {
  const APPLICATION_STATES = getApplicationSteps(applicationStateGroups);

  if (ERROR_STATES.includes(step)) {
    step = APPLICATION_STATES[APPLICATION_STATES.indexOf(step) - 1];
  }
  const nonFailedStates = getNonFailedSteps(APPLICATION_STATES);

  const stepIndex = nonFailedStates.indexOf(step);
  return stepIndex;
};

export const getApplicationProgressPercentage = (currentState, applicationStateGroups) => {
  const currentStateIndex = getStepIndex(currentState, applicationStateGroups);

  const nonFailedStates = getNonFailedSteps(getApplicationSteps(applicationStateGroups));
  const totalStates = nonFailedStates.length;
  const percentage = (currentStateIndex / (totalStates - 1)) * 100;
  return !percentage ? 0 : percentage > 0 ? (percentage > 100 ? 100 : Math.ceil(percentage)) : 0;
};

export const loadCheckoutScript = () => {
  // eslint-disable-next-line consistent-return
  return new Promise((resolve, reject) => {
    if (window.Razorpay) return resolve();

    const script = document.createElement('script');
    script.src = 'https://checkout.razorpay.com/v1/checkout.js';
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
  });
};

export const validateMobileNumber = (input) => {
  const isValidMobile = isMobile(input);
  const isLengthValid = input.length === 10;
  const isFirstCharValid = Number(input.charAt(0)) >= 6;
  const isValid = !!(isValidMobile && isLengthValid && isFirstCharValid);

  if (isValid) {
    return '';
  }

  return 'Please enter valid mobile number';
};

export const createFormData = (form = {}) => {
  const formData = new FormData();

  Object.keys(form).forEach((key) => {
    formData.append(key, form[key]);
  });

  return formData;
};

export const toBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
  });
};

export const checkifDateExpired = (dateToCheck) => {
  const today = new Date();
  return !!(dateToCheck.setHours(0, 0, 0, 0) <= today.setHours(0, 0, 0, 0));
};

export const getSettlementStatus = (merchantId, callbackSettlementStatus) => {
  const parseSettlementCallbackStatus = () => {
    if (callbackSettlementStatus === 'settlementDone') return true;
    else if (callbackSettlementStatus === 'disableAnimation') return false;
    return false;
  };
  const settlementCallbackStatus = parseSettlementCallbackStatus();

  const merchantsSettlementStatus =
    JSON.parse(LocalStorageService.getItem('merchantsSettlementStatus')) || {};

  if (callbackSettlementStatus) {
    const updatedMerchantSettlementStatus = {
      ...merchantsSettlementStatus,
      [merchantId]: settlementCallbackStatus ? true : 'disableAnimation',
    };

    LocalStorageService.setItem(
      'merchantsSettlementStatus',
      JSON.stringify(updatedMerchantSettlementStatus),
    );
    return settlementCallbackStatus ? true : 'disableAnimation';
  } else {
    const settlementStatusExists = Object.prototype.hasOwnProperty.call(
      merchantsSettlementStatus,
      merchantId,
    );
    if (settlementStatusExists) return merchantsSettlementStatus[merchantId];
    else return true;
  }
};

export const getDateSuffix = (date) => {
  if (date > 3 && date < 21) return 'th';
  switch (date % 10) {
    case 1:
      return 'st';
    case 2:
      return 'nd';
    case 3:
      return 'rd';
    default:
      return 'th';
  }
};

const CAPITAL_PRODUCTS = [
  {
    label: 'Corporate Cards',
    productType: COLLECTIONS_PRODUCT_TYPES.CARDS,
    flags: ['isCardsEnabled'], // isCardsLOSEnabled = capital_cards_eligible
    // disabledFlag: 'disable_cards_post_dpd',
  },
  {
    label: 'Cash Advance',
    productType: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
    flags: ['isWithdrawFeatureEnabled'], // to identify is products available for the merchant. 'loc', 'loc_stage_2', 'los' to
    // disabledFlag: 'disable_loc_post_dpd', // product avail but disabled due to dpd or something
  },
  {
    label: 'Working Capital loan',
    productType: COLLECTIONS_PRODUCT_TYPES.LOANS,
    flags: ['isLoansEnabled'],
    // disabledFlag: 'disable_loans_post_dpd',
  },
];
/**
 * @param {Object} user User Instance. used to access feature flags
 * @returns {Array} Array of product types active for merchants
 */
const getActiveCapitalProductsForMerchant = (user) =>
  CAPITAL_PRODUCTS.filter((product) => product.flags.every((flag) => user[flag])).map(
    (product) => product.productType,
  );
/**
 * @param {Array} reasons Array of product types
 * @returns {String} comma seprate product names derived from product types
 */
export const getProductNames = (reasons) =>
  CAPITAL_PRODUCTS.filter((product) => reasons.includes(product.productType)).reduce(
    (names, product, idx, srcArray) => {
      const lastElement = srcArray.length - 1 === idx;
      const prefix = names.length ? (lastElement ? ' and ' : ' , ') : '';
      names += `${prefix}${product.label}`;
      return names;
    },
    '',
  ); // ex:- Cash Advance, or Corporate Card.
/**
 * get which producst disabled
 * @param {Object} user User Instance
 * @param {String} productType values of COLLECTIONS_PRODUCT_TYPES
 * @returns {Array} Promise resolves into Array of product types
 */
export const getDisabledReasons = async (user, productType) => {
  const data = await api.getProductDisabledReason(productType).catch(() => []);
  const prods = data.map((prod) => prod.product_type);

  return prods.length ? prods : getActiveCapitalProductsForMerchant(user);
};

/**
 * disable future dates only
 * @param {Object} date Date Object
 * @returns {Boolean} disable the future date or not given the condition.
 */

export const disableFutureMonths = (date) => {
  if (!date) return false;
  const currentMonth = moment().month();
  const currentYear = moment().year();
  return date?.month() > currentMonth && date?.year() >= currentYear;
};

export const getProductType = (user) => {
  return user.isFeatureEnabled('cash_on_card') ? CASH_ADVANCE_PRODUCT_TYPES.CASH_ON_CARD : '';
};

const isRegionIN = (user) => {
  return user.merchant.country_code === 'IN';
};

/** Product is being deprecated, so only active merchants can view product */
export const canViewCashAdvanceProduct = (user) => {
  // All Cash Adance merchants should have loc feature flag(withdraw_loc is common for LOC and LOC_EMI)
  // isAllowedView has checks for current user role and white labelled orgs - admin and owner can access
  return (
    user.isOrgRZP &&
    isRegionIN(user) &&
    (user.isLOCEnabled || user.isCashOnCardEnabled) &&
    user.isAllowedView('cash_advance') &&
    (user.isWithdrawFeatureEnabled || user.isCashOnCardEnabled)
  );
};

export const isCashAdvanceProductActive = (user) => {
  return canViewCashAdvanceProduct(user);
};

/** Product is being deprecated, so only active merchants can view product */
export const canViewLOCEMIProduct = (user) => {
  return (
    !canViewCashAdvanceProduct(user) &&
    user.isOrgRZP &&
    isRegionIN(user) &&
    user.isLOCEMIEnabled &&
    user.isAllowedView('cash_advance') &&
    user.isWithdrawFeatureEnabled
  );
};

export function getCashOnCardRenderDateKey() {
  const user = store.getState().session.user.id;
  return `${CASH_ON_CARD_RENDER_DATE_KEY}--${user}`;
}

export function getCashOnCardRenderDate() {
  return LocalStorageService.getItem(getCashOnCardRenderDateKey());
}

export const setCashOnCardRenderDate = (value) => {
  if (!getCashOnCardRenderDate()) {
    LocalStorageService.setItem(getCashOnCardRenderDateKey(), value);
  }
};

export const getXCardsBaseURL = () => {
  switch (window.APP_ENV) {
    case 'stage':
    case 'beta':
    case 'dev':
      return 'https://x.np.razorpay.in/cards';
    case 'echo':
      return 'https://x-echo.np.razorpay.in/cards';
    case 'func':
      return 'https://x-func.np.razorpay.in/cards';
    case 'prod':
    case 'production':
      return 'https://x.razorpay.com/cards';
    default:
      return 'https://x.razorpay.com/cards';
  }
};

export const isMerchantNewToCashOnCard = () => {
  const renderDate = getCashOnCardRenderDate();
  if (renderDate) {
    const diff = moment().diff(moment(renderDate), 'days');
    return diff <= 3;
  } else {
    setCashOnCardRenderDate(moment().format('YYYY-MM-DD'));
    return true;
  }
};
