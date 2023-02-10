import { INPUT_VALIDATION_STATES } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import {
  getBankAccountBannerContent,
  getBankName,
  getMaskedPanNumber,
  isFormInputValid,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form/utils';
import { validDataState } from './fixtures/constants';

describe('Bank Account Update - Form Utils', () => {
  describe('getBankName Fn', () => {
    test('should return a string concating bank name and branch', () => {
      const bankData = {
        BANK: 'IDFC Bank',
        BRANCH: 'Aundh, Pune',
      };
      expect(getBankName(bankData)).toBe('IDFC Bank, Aundh, Pune');
    });
    test('should return empty string if BANK is not defined', () => {
      const bankData = {
        BANK: '',
        BRANCH: 'Aundh, Pune',
      };
      expect(getBankName(bankData)).toBe('');
    });
  });

  describe('getMaskedPanNumber Fn', () => {
    test('should return masked pan number', () => {
      expect(getMaskedPanNumber('GOEPS1199S')).toBe('GOExxx9S');
    });
  });

  describe('getBankAccountBannerContent Fn', () => {
    test('should return correct pan banner content based on business type', () => {
      const panData = {
        promoter_pan: 'FOEPS1199P',
        company_pan: 'GOEPS1199S',
        business_type: '1',
      };
      expect(getBankAccountBannerContent(panData)).toBe(
        `The bank account must belong to the business PAN holder ${getMaskedPanNumber(
          panData.company_pan,
        )} or signatory PAN holder ${getMaskedPanNumber(panData.promoter_pan)} only.`,
      );
    });
  });

  describe('isFormInputValid Fn', () => {
    test('should return false when some field is not valid', () => {
      expect(
        isFormInputValid({
          ...validDataState,
          inputValidation: {
            ...validDataState.inputValidation,
            account_number: INPUT_VALIDATION_STATES.ERROR,
          },
        }),
      ).toBe(false);
    });
  });
});
