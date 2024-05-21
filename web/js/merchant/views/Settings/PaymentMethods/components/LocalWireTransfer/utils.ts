import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

import { ACTIVATED, MCC_CODE_NOT_ELIGIBLE_ERROR } from './constants';
import { AccountType, LeafListType } from './types';

//functions
export const openSupport = (): void => {
  CreateTicketEmitter.emit('create-ticket', 'tickets');
};

export const hasMCCInEligibleError = (errorMessage: unknown): boolean => {
  if (errorMessage && typeof errorMessage === 'string') {
    return errorMessage.toLowerCase().includes(MCC_CODE_NOT_ELIGIBLE_ERROR);
  }

  return false;
};

export const transformError = (error) => {
  let errorMessage = '';
  if (error && typeof error === 'object' && 'errors' in error && Array.isArray(error.errors)) {
    errorMessage = error.errors[0];
  } else if (error instanceof Error) {
    errorMessage = error.message;
  } else {
    errorMessage = 'Something went wrong. Please try again later.';
  }
  return errorMessage;
};

export const getPublicPaymentLinkForContainer = (
  leafList: LeafListType,
  accounts: Array<AccountType>,
  publicPaymentLink: string | undefined,
) => {
  let paymentLink;
  leafList.list.forEach((account) => {
    if (accounts.find((acc) => acc.va_currency === account.vaCurrency)?.status === ACTIVATED) {
      paymentLink = publicPaymentLink;
    }
  });
  return paymentLink;
};
