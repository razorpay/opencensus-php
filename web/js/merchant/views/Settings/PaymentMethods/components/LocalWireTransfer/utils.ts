import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

//functions
export const openSupport = (): void => {
  CreateTicketEmitter.emit('create-ticket', 'tickets');
};
