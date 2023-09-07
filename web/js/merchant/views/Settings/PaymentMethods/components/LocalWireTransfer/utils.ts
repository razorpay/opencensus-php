import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

import { MCC_CODE_NOT_ELIGIBLE_ERROR } from './constants';

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
