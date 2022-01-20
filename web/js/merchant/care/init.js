import * as EventEmitter from 'eventemitter3';
import loadScript from 'common/utils/loadScript';

export const TicketSystemEmitter = new EventEmitter();
export const initCare = (user, firstCall) => {
  if (user.isFrontendCareActive) {
    window.rzpTicketSystem.openModal = (id, initialData = {}) => {
      TicketSystemEmitter.emit('openModal', id, initialData);
    };
    window.rzpTicketSystem.closeModal = () => {
      TicketSystemEmitter.emit('closeModal');
    };
    if (firstCall.id) {
      window.rzpTicketSystem.openModal(firstCall.id, firstCall.data);
    }
  } else {
    loadScript(`https://cdn.razorpay.com/static/ticket-system/bundle.js`, {
      defer: true,
      async: true,
    }).then(() => {
      if (firstCall.id) {
        window.rzpTicketSystem.openModal(firstCall.id, firstCall.data);
      }
    });
  }
};
