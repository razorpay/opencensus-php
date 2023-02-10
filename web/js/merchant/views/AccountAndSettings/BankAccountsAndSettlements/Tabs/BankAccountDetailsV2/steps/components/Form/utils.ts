import {
  PROPRIETORSHIP,
  INDIVIDUAL,
  NOT_REGISTERED,
} from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import {
  Bank,
  IinputData,
  IinputValidation,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import IUser from 'merchant/models/User';

export const getBankName = (bank: Bank): string => {
  if (!bank?.BANK || !bank.BRANCH) return '';
  return `${bank.BANK}, ${bank.BRANCH}`;
};

export const getMaskedPanNumber = (panNumber: string): string => {
  if (panNumber) {
    return `${panNumber.substring(0, 3)}xxx${panNumber.substring(panNumber.length - 2)}`;
  }
  return 'xxxxxxxxx';
};

export const getBankAccountBannerContent = ({
  promoter_pan,
  company_pan,
  business_type,
}: {
  promoter_pan: string;
  company_pan: string;
  business_type: string;
}): string => {
  const promoterPan = getMaskedPanNumber(promoter_pan);
  const companyPan = getMaskedPanNumber(company_pan);

  switch (Number(business_type)) {
    case PROPRIETORSHIP:
      return `The bank account must belong to the business PAN holder ${companyPan} or signatory PAN holder ${promoterPan} only.`;
    case INDIVIDUAL:
    case NOT_REGISTERED:
      return `The bank account must belong to the PAN holder ${promoterPan} only.`;
    default:
      return `The bank account must belong to the business PAN holder ${companyPan} only.`;
  }
};

export const isFormInputValid = ({
  inputData,
  inputValidation,
}: {
  inputData: IinputData;
  inputValidation: IinputValidation;
}): boolean => {
  for (const key in inputData) {
    if (!inputData[key]) {
      return false;
    }
  }
  for (const key in inputValidation) {
    if (inputValidation[key] === 'error') {
      return false;
    }
  }
  return true;
};

export const getUpdatePayload = (inputData: IinputData, user: IUser): FormData => {
  const body = {
    account_number: inputData.account_number,
    ifsc_code: inputData.ifsc_code,
    beneficiary_name: inputData.beneficiary_name,
    sync_only: true,
    beneficiary_email: user?.email,
    beneficiary_mobile: user?.contact_mobile,
  };

  const formdata = new FormData();
  for (const prop in body) if (body[prop]) formdata.append(prop, body[prop]);

  return formdata;
};
