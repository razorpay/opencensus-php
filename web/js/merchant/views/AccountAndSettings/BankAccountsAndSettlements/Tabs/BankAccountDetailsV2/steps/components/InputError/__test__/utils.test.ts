import { BankAccountUpdateErrorCodeEnum } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { mapErrorCodeToData } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/InputError/constants';
import {
  getBVSErrorCodeFromErrors,
  getErrorDataFromCode,
  isErrorCodeSupported,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/InputError/utils';

describe('Bank Account Update - InputError Utils', () => {
  describe('isErrorCodeSupported Fn', () => {
    test('should return true if error code is supported', () => {
      expect(isErrorCodeSupported(BankAccountUpdateErrorCodeEnum.ACCOUNT_BLOCKED)).toBe(true);
    });
    test('should return false if error code is not supported', () => {
      expect(isErrorCodeSupported('any random error')).toBe(false);
    });
  });

  describe('getErrorDataFromCode Fn', () => {
    test('should return expected error data', () => {
      expect(getErrorDataFromCode(BankAccountUpdateErrorCodeEnum.ACCOUNT_BLOCKED)).toBe(
        mapErrorCodeToData.KC05,
      );
    });
    test('should return default error data if error code is not passed', () => {
      expect(getErrorDataFromCode('any random error')).toBe(mapErrorCodeToData.KC03);
    });
  });

  describe('getBVSErrorCodeFromErrors Fn', () => {
    test('should extract error code from passed errors', () => {
      expect(getBVSErrorCodeFromErrors(['KC07: Account Closed', 'Status Code: 400'])).toBe(
        BankAccountUpdateErrorCodeEnum.ACCOUNT_CLOSED,
      );
    });

    test('should return empty string if error code is not presemt in passed errors', () => {
      expect(getBVSErrorCodeFromErrors('')).toBe('');
      expect(getBVSErrorCodeFromErrors(([122, 22] as unknown) as string[])).toBe('');
    });
  });
});
