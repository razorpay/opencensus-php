import {
  BankAccountUpdateErrorCodeEnum,
  BankAccountUpdateErrorData,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { mapErrorCodeToData } from './constants';

export const getBVSErrorCodeFromErrors = (errors: string[] | undefined | null | string): string => {
  try {
    if (errors?.length) {
      return errors[0].split(':')[0].trim();
    }
    return '';
  } catch (_err) {
    return '';
  }
};

export const isErrorCodeSupported = (errorCode: string): boolean =>
  Object.keys(mapErrorCodeToData).includes(errorCode);

export const getErrorDataFromCode = (errorCode: string): BankAccountUpdateErrorData =>
  mapErrorCodeToData[errorCode] ||
  mapErrorCodeToData[BankAccountUpdateErrorCodeEnum.INVALID_BENEFICIARY_ACCOUNT_NUMBER];
