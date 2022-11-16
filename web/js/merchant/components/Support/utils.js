export const fireCustomEvent = ({ event = 'custom-event', data = {}, elementId = '' }) => {
  if (document && window && window.CustomEvent) {
    const customEv = new CustomEvent(event, { detail: data });
    if (elementId) {
      document.getElementById(elementId).dispatchEvent(customEv);
    } else {
      document.dispatchEvent(customEv);
    }
  }
};
