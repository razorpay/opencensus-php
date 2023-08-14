import { getItem } from 'common/utils/localStorage';
import {
  B2B_PURPOSE_CODE_INELIGIBLE_ERROR_KEY,
  B2B_ACCOUNT_STATUS,
  PURPOSE_CODE_NOT_ELIGIBLE_ERROR,
} from './constants';

/**
 * Extracts virtual account details from the given data.
 *
 * @param {Object|Array} data - The data to extract account details from.
 */
export function extractVirtualAccountDetails(data) {
  const {
    accounts = [],
    status = '',
    reason = '',
  } = Array.isArray(data) ? { accounts: data } : data;

  const accountsDeactivated = status === B2B_ACCOUNT_STATUS.deactivated;

  const isIneligiblePurposeCodeModalOpen = accountsDeactivated
    ? !getItem(B2B_PURPOSE_CODE_INELIGIBLE_ERROR_KEY)
    : false;

  return {
    reason,
    accounts,
    accountsDeactivated,
    isIneligiblePurposeCodeModalOpen,
  };
}

/**
 * Extracts the purpose code error from the given errors array.
 *
 * @param {Array|string} errors - The array of errors or a single error string.
 * @return {{isIneligiblePurposeCodeModalOpen: boolean, error: string}} - An object containing the
 *     isIneligiblePurposeCodeModalOpen flag and the error message.
 */
export function extractPurposeCodeError(errors) {
  let isIneligiblePurposeCodeModalOpen = false;
  let error = '';
  const errorCode = Array.isArray(errors) ? errors[0] : errors;

  // check for ineligible purpose code error;
  if (
    !getItem(B2B_PURPOSE_CODE_INELIGIBLE_ERROR_KEY) &&
    errorCode?.toLowerCase().includes(PURPOSE_CODE_NOT_ELIGIBLE_ERROR)
  ) {
    isIneligiblePurposeCodeModalOpen = true;
    error = errorCode;
  }

  if (!error) {
    error = errorCode;
  }

  return { isIneligiblePurposeCodeModalOpen, error };
}
