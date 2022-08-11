import LottieLoadingData from 'merchant/helpers/lottieConfigs/PennyTestingLoading.json';
import LottieSuccessData from 'merchant/helpers/lottieConfigs/PennyTestingSuccess.json';

const tryDifferentAccount =
  "The given bank account couldn't be verified. Try again with another account.";
const retrySameAccount = "The given bank account couldn't be verified. Check details and try again";

export const BankVerificationErrorInDetailsMap = {
  'KC03: Invalid Beneficiary Account Number or IFSC': {
    title: 'Invalid Beneficiary Account Number or IFSC Code',
    subtitle: retrySameAccount,
    icon: 'account-number',
  },
  'KC40: Invalid Beneficiary IFSC Code or NBIN': {
    title: 'Invalid Beneficiary IFSC Code or NBIN',
    subtitle: retrySameAccount,
    icon: 'invalid-ifsc',
  },
  'KC27: Invalid Account': {
    title: "Couldn't verify account",
    subtitle: retrySameAccount,
    icon: 'couldnt-verify',
  },
  'KC05: Account Blocked/Frozen': {
    title: 'Account blocked/frozen',
    subtitle: tryDifferentAccount,
    icon: 'account-blocked',
  },
  'KC06: NRE Account': {
    title: 'NRE account not supported',
    subtitle: tryDifferentAccount,
    icon: 'nre-account',
  },
  'KC07: Account Closed': {
    title: 'Account closed',
    subtitle: tryDifferentAccount,
    icon: 'account-closed',
  },
};

const pageDismissalText = "Please don't press back or close the page";

export const BANK_ACCOUNT_UPDATE_UNDER_REVIEW = {
  title: 'Your bank account change request is under review',
  subtitle:
    "This should take 2-3 working days. We'll reach out to you if we need any other details or when it's complete.",
  icon: 'bank-circle',
};

export const BANK_ACCOUNT_UPDATE_FILE_UPLOAD = {
  lottieData: LottieLoadingData,
  title: 'Uploading your bank account statement',
  subtitle: pageDismissalText,
  info:
    "We're uploading your bank account statement for our team to review. This may take a few seconds.",
};

export const BANK_ACCOUNT_UPDATE_SUBMIT_DETAILS = {
  lottieData: LottieLoadingData,
  title: 'Submitting your bank details',
  subtitle: pageDismissalText,
  info: "We're submitting your bank details for our team to review. This may take a few seconds.",
};

export const BANK_ACCOUNT_UPDATE_PENNY_TESTING_LOADING = {
  lottieData: LottieLoadingData,
  title: 'Verifying your bank details',
  subtitle: pageDismissalText,
  info: "We're depositing ₹1 to the account for verification. This may take a few seconds.",
};

export const BANK_ACCOUNT_UPDATE_PENNY_TESTING_SUCCESS = {
  lottieData: LottieSuccessData,
  title: 'Bank account changed successfully',
  subtitle: "We'll now process your settlements to this account",
};
