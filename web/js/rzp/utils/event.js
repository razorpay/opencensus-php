import { isFunction } from './rzp-utils';

function createEvent(name, options = {}) {
  if (isFunction(window.Event)) {
    return new window.Event(name, options);
  }

  const event = document.createEvent('Event'),
    { bubbles } = options;

  event.initEvent(name, !!bubbles);

  return event;
}

export default createEvent;
