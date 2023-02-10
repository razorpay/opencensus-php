import LottieLoadingData from 'merchant/helpers/lottieConfigs/PennyTestingLoading.json';
import LottieSuccessData from 'merchant/helpers/lottieConfigs/PennyTestingSuccess.json';
import {
  LoadingStepData,
  LOADING_STATE,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';

export const LOADING_STEP_DATA: Record<LOADING_STATE, LoadingStepData> = {
  [LOADING_STATE.UPLOAD_VERIFICATION_LETTER_DETAIL]: {
    title: 'Uploading your bank verification letter',
    subTitle: "Please don't press back or close the page",
    description:
      "We're uploading your bank verification letter for our team to verify. This can take a few seconds.",
    animationData: LottieLoadingData,
  },
  [LOADING_STATE.UPLOAD_CANCELLED_CHEQUE_DETAIL]: {
    title: 'Uploading your video of cancelled cheque',
    subTitle: "Please don't press back or close the page",
    description:
      "We're uploading your video of cancelled cheque for our team to verify. This can take a few seconds.",
    animationData: LottieLoadingData,
  },
  [LOADING_STATE.UPLOAD_NC_BANK_DETAIL]: {
    title: 'Submitting your bank details',
    subTitle: "Please don't press back or close the page",
    description:
      "We're submitting your bank details for our team to review. This may take a few seconds.",
    animationData: LottieLoadingData,
  },
  [LOADING_STATE.PENNY_TESTING_INPROGRESS]: {
    title: 'Verifying your bank details',
    subTitle: "Please don't press back or close the page",
    description:
      "We're depositing ₹1 to the account for verification. This may take a few seconds.",
    animationData: LottieLoadingData,
  },
  [LOADING_STATE.PENNY_TESTING_SUCCESS]: {
    title: 'Bank account successfully updated',
    subTitle: 'Settlements will be processed on this bank account.',
    mobileSubTitle: 'We’ll now process your settlements to this account',
    animationData: LottieSuccessData,
    closeCTALabel: 'Okay, got it',
  },
};
