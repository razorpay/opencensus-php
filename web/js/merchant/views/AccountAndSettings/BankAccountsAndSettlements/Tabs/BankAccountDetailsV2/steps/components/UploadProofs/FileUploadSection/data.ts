import {
  FormDataInterface,
  LOADING_STATE,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';

export const FORM_DATA: Record<
  LOADING_STATE.UPLOAD_CANCELLED_CHEQUE_DETAIL | LOADING_STATE.UPLOAD_VERIFICATION_LETTER_DETAIL,
  FormDataInterface
> = {
  [LOADING_STATE.UPLOAD_CANCELLED_CHEQUE_DETAIL]: {
    description: {
      title:
        'Record and submit a video of you cancelling a cheque of the  bank account (belonging to the business PAN holder EUCxxx9K only)',
      details: {
        title: 'The following details must be clearly visible:',
        items: ['Beneficiary name', 'Account number', 'IFSC code'],
      },
      video: {
        link: 'https://youtu.be/MRC4sl23olw',
      },
    },
    document: {
      subtitle: '(MOV or MP4 file under 50 MB only)',
    },
  },
  [LOADING_STATE.UPLOAD_VERIFICATION_LETTER_DETAIL]: {
    description: {
      title:
        'Click and submit a picture of a verification letter from your bank stating that the account belongs to you (the business PAN holder EUCxxx9K only). This should be on bank’s official letter head and duly signed by an authorised signatory of the bank. ',
      details: {
        title: 'The following details must be clearly visible:',
        items: ['Beneficiary name', 'Account number', 'IFSC code'],
      },
    },
    document: {
      subtitle: '(IMG or PNG file under 50 MB only)',
    },
  },
};
