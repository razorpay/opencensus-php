import { allowedVideoExtensions } from 'merchant/components/File/constants';
import { LOADING_STATE } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import FileUploadSection from './FileUploadSection';

export const TabData = [
  {
    id: LOADING_STATE.UPLOAD_CANCELLED_CHEQUE_DETAIL,
    title: 'Video of cancelled cheque (recommended)',
    component: FileUploadSection,
    extras: {
      acceptFiles: allowedVideoExtensions,
    },
  },
  {
    id: LOADING_STATE.UPLOAD_VERIFICATION_LETTER_DETAIL,
    title: 'Bank verification letter',
    component: FileUploadSection,
    extras: {
      acceptFiles: ['jpg', 'png', 'pdf'],
    },
  },
];
