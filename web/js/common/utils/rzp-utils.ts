import { getAllCountries, CurrencyCodeType, CountryCodeType } from '@razorpay/i18nify-js';
import { convertToMajorUnit, getCurrencySymbol, formatNumber } from '@razorpay/i18nify-js/currency';
import { getDialCodeByCountryCode } from '@razorpay/i18nify-js/phoneNumber';
import isEmpty from 'lodash/isEmpty';
import { COUNTRY_CODES } from '@libs/web-nexus/common/components/CountryCodeInput/constant';
import abExperimentsMap from '@dashboards/payments/utils/abExperimentsMap';
import { ANALYTICS } from '@libs/web-nexus/common/constant';
import { analyticsTrack } from './rzp-utils';
import { getFormattedAmount } from './rzp-utils';

export const numberFormatRegex = /(.{1,2})(?=.(..)+(\...)$)/g;

/**
 * @deprecated Use getCurrencySymbol from i18nify-js
 */
export const currencySymbols = {
  INR: '₹',
  USD: 'US$',
  MYR: 'RM',
};

export const getMajorAmountFromMinorUnit = (amount, currency = 'INR') => {
  try {
    return convertToMajorUnit(amount, { currency });
  } catch (error) {
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.CURRENCY,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `amount: ${amount}, currency: ${currency}`,
        error: `${error}`,
      },
    });
    return (Number(amount) / 100).toFixed(2);
  }
};

export const getSplitzExperimentVariant = (experimentName) => {
  const splitzExperiments = window.rzp_user?.splitz_experiments;
  let splitzExperimentVariant = null;

  if (splitzExperiments) {
    Object.keys(splitzExperiments).forEach((experimentId) => {
      const splitzExperiment = splitzExperiments[experimentId];
      if (abExperimentsMap[experimentName]?.includes(experimentId) && !isEmpty(splitzExperiment)) {
        splitzExperimentVariant = splitzExperiment;
      }
    });
  }
  return splitzExperimentVariant || {};
};

/**
 * Formats a monetary amount with its corresponding currency symbol.
 * The result is a human-readable monetary string.
 */
export const createI18nifyCurrencyFormattedString = (
  amount,
  currency: CurrencyCodeType = 'INR',
) => {
  // Retrieve formatted components of the amount.

  let updatedAmount, integer, fraction, formattedAmount;
  try {
    updatedAmount = convertToMajorUnit(amount, { currency }).toString();
    formattedAmount = formatNumber(updatedAmount, {
      currency,
      intlOptions: {
        style: 'currency',
      },
    });
  } catch (error) {
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.CURRENCY,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `amount: ${amount}, currency: ${currency}`,
        error: `${error}`,
      },
    });
    updatedAmount = (amount / 100).toFixed(2);
    integer = updatedAmount.split('.')[0] || '';
    fraction = updatedAmount.split('.')[1] || '';

    formattedAmount = `${Number(integer) < 0 ? '-' : ''}${currency} ${Math.abs(
      integer,
    )}.${fraction}`;
  }

  return formattedAmount.trim();
};

export const getFormattedAmountWithSymbol = (amount, currency: CurrencyCodeType = 'INR') => {
  return `${getCurrencySymbol(currency)} ${getFormattedAmount(amount, currency)}`;
};

export const is2FaExperimentEnabled = (experimenets) =>
  experimenets?.enable_2fa_for_protected_flows?.variables.result === 'on';

export const colors = ['primary', 'success', 'info', 'warn', 'danger'];

export const paymentStatusColor = {
  captured: colors[1],
  authorized: colors[2],
  refunded: colors[3],
  failed: colors[4],
};

export const intervals = [
  {
    value: 'day',
    label: 'Daily',
  },
  {
    value: 'week',
    label: 'Weekly',
  },
  {
    value: 'month',
    label: 'Monthly',
  },
  {
    value: 'year',
    label: 'Yearly',
  },
];

/**
 * Method to get eventCategory for Analytics based on the given pathname
 * @param {String} pathname
 * @return {String}
 */
export const getEventCategoryFromPath = (pathname) => {
  // Remove slashes from path. Eg: /plans/ => plan
  pathname = pathname && pathname.split('/').join('');
  switch (pathname) {
    case 'payments':
      return 'Dashboard - Payments';
    case 'refunds':
      return 'Dashboard - Refunds';
    case 'paymentlinks':
      return 'Dashboard - Payment Links';
    case 'orders':
      return 'Dashboard - Orders';
    case 'settlements':
      return 'Dashboard - Settlements';
    case 'invoices':
    case 'items':
      return 'Dashboard - Invoices';
    case 'plans':
    case 'subscriptions':
      return 'Dashboard - Subscriptions';
    case 'virtualaccounts':
      return 'Dashboard - Smart Collect';
    default:
      return 'Dashboard - Home';
  }
};

/**
 * Get value for `gridTemplateColumns` property.
 * Filters out args that are not of type `string`. Does not check if passed strings are valid or not.
 *
 * @param  {...string} args Value for width of each column.
 * @returns {string|undefined} All filtered values concatenated with a space.
 */
export function getTableTemplateColumnsValue(...args) {
  return args.filter((value) => typeof value === 'string' && value !== '').join(' ') || undefined;
}

export const is2faRouteExperimentEnabled = (experimenets) =>
  experimenets?.two_fa_route?.variables.result === 'on';

export const getDialCodeFromCountryCode = (countryCode = 'IN' as CountryCodeType) => {
  let dialCode;
  try {
    dialCode = getDialCodeByCountryCode(countryCode);
  } catch (error) {
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.PHONE_NUMBER,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `countryCode: ${countryCode}`,
        error: `${error}`,
      },
    });
    dialCode = '+91';
  }
  return dialCode;
};

export const getCountryCodes = async () => {
  try {
    const geoData = await getAllCountries(); // Check response here: https://geosmart.razorpay.com/#/geo/getAllCountries
    let result: typeof COUNTRY_CODES = [];

    for (const code in geoData) {
      if (geoData.hasOwnProperty(code)) {
        const country = geoData[code];
        result.push({
          name: country.country_name,
          dial_code: country.dial_code,
          code,
        });
      }
    }
    return result;
  } catch (error) {
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.GEO,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `function: getAllCountries`,
        error: `${error}`,
      },
    });
    return COUNTRY_CODES;
  }
};

export const decodeHTMLEntities = (input) => {
  const txt = document?.createElement?.('textarea');
  txt.innerHTML = input;
  return txt.value;
};

export {
  convertToLocale,
  analyticsTrack,
  COUNTRY_CODES,
  isMobileResolution,
  isFunction,
  isDefined,
  toTitleCase as titleCase,
  humanize,
  capitalizeFirstLetter,
  getCommonAnalyticsProperties,
  isStringAlphabetAndNumberOnly,
  getCommonSegmentProperties,
  makeArray,
  arrayDiff,
  isBlank,
  isPresent,
  isNone,
  findBy,
  filterBy,
  mapBy,
  arrayToObject,
  groupBy,
  pipe,
  normalizeDate,
  formatFromNow,
  daysFromToday,
  getCurrentFinancialYear,
  normalizeBoolean,
  getFixedNumber,
  getFixedINRAmount,
  getFormattedAmount,
  getFormattedAmountNew,
  getFormattedAmountByParts,
  getFormattedNumber,
  formatCurrencyWithAmount,
  formatAmount,
  getCurrencyConfig,
  without,
  truncatedString,
  truncateString,
  pickProps,
  rupeesToPaise,
  paiseToRupees,
  objectDiff,
  stringifyQueryParams,
  stringifyQueryParamsWithPipe,
  getURLQueryParams,
  noop,
  mergeCurrencyFormatting,
  getIntervalCycle,
  getCustomerDisplayName,
  flattenObject,
  getPercentage,
  getPercentages,
  getEMI,
  arrayMove,
  arrayObjToCsv,
  arrayToCsv,
  arrayToCsvDataUrl,
  arrayToSentence,
  checkIfHTTPS,
  autoPrefixUrls,
  getApplicableGSTForSlab,
  getGSTSlabs,
  stringifyAddress,
  readableFileSize,
  getKeysSeparatedByPipe,
  trim,
  pluralize,
  getCountryPINcodeType,
  isAddressValid,
  isAfterDate,
  isAmount,
  isBase64,
  addPrefixToObjectKeys,
  calculateTax,
  camelize,
  capitalize,
  checkIsObjectEmpty,
  checkHTML5APIvalidity,
  getFileTypeIcon,
  getAttachmentExpiryTime,
  getArraySorterFromArray,
  classList,
  convertUnixToDate,
  encodeSensitiveFields,
  decodeSensitiveFields,
  deepClone,
  exportFileAsExcel,
  stringToObj,
  keysToSentence,
  prevent,
  handleNegativeBalanceLimit,
  validateBankDetails,
  getErrorMessageFromResponse,
  linkFromSource,
  isLoggedInViaMobile,
  resolvePath,
  getStartDateFromDiff,
  getYoutubeVideoID,
  scrollToTop,
  updateExtension,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  stringTemplate,
  isProductionEnv,
  randomInt,
  getMobileOperatingSystem,
  isMobileAndTablet,
  monetaryUnitText,
  openTicketModal,
  isConfigTagAPISupported,
  isExperimentEnabled as isExperimentActive,
  uniqueArray,
  getDetailsForIFSC,
  sanitizeHTML,
  setNativeValue,
  loadImage,
  isTaxOfTypeCess,
  subString,
  isValidGSTIN,
  isWebkit,
  getAmountFieldPlaceholder,
  shortenTextBasedOnDashboardAcronyms as shortenText,
  acronymsForShorteningText as acronyms,
  isElementXPercentInViewport,
  isDuplicateWebsite,
  getCanonicalUrl,
} from '@libs/shared-utils';
