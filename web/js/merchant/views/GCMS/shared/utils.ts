import { convertToMajorUnit } from '@razorpay/i18nify-js';
import { formatNumberByParts } from '@razorpay/i18nify-js/currency';
import { upperFirst } from 'lodash';
import html2canvas from 'html2canvas';
import axios, { AxiosRequestConfig, AxiosError, AxiosResponse } from 'axios';

import { SpiltzContextState } from 'common/splitz/types';
import { DASHBOARD_MODE } from '@libs/shared-types';
import { DENOMINATION_TYPE_ENUM } from 'merchant/views/GCMS/shared/constants';
import { ClientError, getPhpBaseUrlForClient, type ResponseWithErrors } from '@libs/shared-utils';
import { getMode } from '@federated/apps/shell/commonStore';
import { isExperimentEnabled } from 'common/splitz/utils';
import { ProgramPolicy, ProgramPriceType } from 'merchant/views/GCMS/Programs/types';
import { VALID_NUMBER_REGEX, NON_NEGATIVE_INTEGER } from 'merchant/views/GCMS/shared/constants';

// TODO: Duplicating fetch instance to prevent throwing
// errors on empty data

const restInstance = axios.create({});

/**
 * Only for UTs to mock with api handlers
 */
restInstance.defaults.baseURL = `${
  Boolean(typeof process?.env?.hostName != 'undefined')
    ? process.env.hostName
    : getPhpBaseUrlForClient()
}`;

export async function fetchDuplicate<T>(
  options: AxiosRequestConfig & { mode?: DASHBOARD_MODE },
): Promise<T> {
  try {
    const response: AxiosResponse<{ data: T; error?: string }> = await restInstance({
      ...options,
      url: `/merchant/api/${options.mode ?? getMode()}/${options.url}`,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json, text/plain, */*',
        ...options?.headers,
      },
    });

    const result = response.data;

    // Handle successful response
    if (response.status >= 200 && response.status <= 204) {
      return result.data;
    } else {
      const errorResult = typeof result === 'string' ? { error: result } : result;
      // @ts-expect-error - Fallback to generic error
      throw new ClientError({ ...errorResult, status: response.status });
    }
  } catch (error) {
    // Handle unauthorized access
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      document.body.dispatchEvent(
        new CustomEvent('NOT_AUTHENTICATED', {
          bubbles: true,
          detail: { continueAjax: () => {} },
        }),
      );
    }

    if (error instanceof ClientError) {
      throw error;
    } else if (isAxiosResponse(error)) {
      throw error;
    } else {
      // @ts-expect-error - Fallback to generic error
      throw new ClientError({ message: 'Unexpected error occurred', status: 400 });
    }
  }
}
// End Duplicating fetch instance

export const getProgramDenomination = ({ policy }: { policy: ProgramPolicy }) => {
  const sortedDenominations = Array.isArray(policy?.gift_card_price_denominations)
    ? policy.gift_card_price_denominations.sort((a, b) => a - b)
    : [];
  if (policy?.gift_card_price_type === ProgramPriceType.FIXED) {
    if (sortedDenominations.length > 1) {
      return `${getFormattedAmountNewDenom(
        sortedDenominations[0],
        true,
      )} - ${getFormattedAmountNewDenom(
        sortedDenominations[sortedDenominations.length - 1],
        true,
      )}`;
    } else {
      return '-';
    }
  } else if (policy?.gift_card_maximum_price && policy?.gift_card_minimum_price) {
    return `${getFormattedAmountNewDenom(
      policy.gift_card_minimum_price,
      true,
    )} - ${getFormattedAmountNewDenom(policy.gift_card_maximum_price, true)}`;
  } else {
    return 'Any';
  }
};

export const formatAmountDenom = (amt, showCurrency, currency) => {
  try {
    const options: {
      intlOptions: {
        minimumFractionDigits: number;
      };
      currency?: string;
    } = {
      intlOptions: {
        minimumFractionDigits: 0,
      },
    };

    if (showCurrency) {
      options.currency = currency;
    }
    const byParts = formatNumberByParts(amt, options as any);
    return byParts.rawParts.reduce((acc, curr) => `${acc}${curr.value}`, '');
  } catch (e) {
    if (window.APP_ENV !== 'production') console.error(e);
    return showCurrency ? `${currency} ${amt}` : amt;
  }
};

export function displayExpiryValidity(program) {
  const {
    policies: {
      gift_card_validity_period,
      gift_card_validity_period_count,
      gift_card_validity_in_days,
    },
  } = program;
  if (gift_card_validity_period) {
    return `${gift_card_validity_period_count} ${upperFirst(gift_card_validity_period)}s`;
  } else if (gift_card_validity_in_days) {
    return `${gift_card_validity_in_days} Days`;
  } else {
    return '-';
  }
}

export const getFormattedAmountNewDenom = (amount, showCurrency, currency = 'INR') => {
  let adjustedAmount;
  try {
    adjustedAmount = convertToMajorUnit(amount, { currency: currency as any }).toString();
  } catch (error) {
    adjustedAmount = (amount / 100).toFixed(2);
  }

  return formatAmountDenom(adjustedAmount, showCurrency, currency);
};

export const isGCMSExperimentEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { razorpay_gcms: undefined } };
  return isExperimentEnabled(abExperiments.razorpay_gcms);
};

export const isGiftCardTransferEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { gift_cards_transfer: undefined } };
  return isExperimentEnabled(abExperiments.gift_cards_transfer);
};

export const isCreateGiftCardBatchEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { create_bulk_gift_cards: undefined } };
  return isExperimentEnabled(abExperiments.create_bulk_gift_cards);
};

export const isNonNegativeInteger = (value: string) => {
  return NON_NEGATIVE_INTEGER.test(value);
};

/* convert unix timestamp to human readable short date format */
export const convertUnixToShortDate = (unixTimeStamp: number) => {
  const date = new Date(unixTimeStamp * 1000).toLocaleString('en-IN', {
    month: 'short',
    day: 'numeric',
    year: '2-digit',
  });
  return date;
};

export const captureDivAsImage = async (ref, cb) => {
  const div = ref.current; // Target div

  if (!div) return;
  // Convert div content to canvas
  const canvas = await html2canvas(div, { useCORS: true });

  // Convert canvas to Blob
  canvas.toBlob((blob) => {
    if (!blob) return;

    cb(new File([blob], 'image.png', { type: 'image/png' }));
    // Create FormData and append Blob
  });
};

export function isValidNumber(number: string) {
  return VALID_NUMBER_REGEX.test(number);
}



export function isNonNegativeIntegerOrEmpty(value: string) {
  return isNonNegativeInteger(value) || value === '';
}
