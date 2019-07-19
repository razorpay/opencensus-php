import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Invoice';

export const track = setTrackData({
  eventCategory,
});

/*
* Track click on duplicate invoice button
* */
export function trackClickDuplicateInvoice() {
  track({
    eventAction: 'Click - Duplicate Invoice',
  });
}

/*
* Track click on saving duplicate invoice
* */
export function trackSaveDuplicateInvoice() {
  track({
    eventAction: 'Save - Duplicate Invoice',
  });
}
