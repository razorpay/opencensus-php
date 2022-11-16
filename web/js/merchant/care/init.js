import * as EventEmitter from 'eventemitter3';

export const TicketSystemEmitter = new EventEmitter();
export const initCare = (user, firstCall) => {
  window.rzpTicketSystem.openModal = (id, initialData = {}) => {
    TicketSystemEmitter.emit('openModal', id, initialData);
  };
  window.rzpTicketSystem.closeModal = () => {
    TicketSystemEmitter.emit('closeModal');
  };
  if (firstCall.id) {
    window.rzpTicketSystem.openModal(firstCall.id, firstCall.data);
  }
};
