import { setTrackData } from 'common/utils/googleAnalytics';

const eventCategory = 'Dashboard - Subscription - Auth Creation';

export const track = setTrackData({
  eventCategory,
});

export function trackClickNext(type) {
  track({
    eventAction: `Next - ${type}`,
  });
}

export function trackClickPaymentMethod(value) {
  track({
    eventAction: `${value} - Payment Method`,
  });
}

export function trackSkipBankDetails(event) {
  track({
    eventAction: `${event.target.checked ? 'Checked' : 'Unchecked'} - Skip bank details`,
  });
}

export function trackCloseCreateForm() {
  track({
    eventAction: 'Close - Create Form',
  });
}
export function trackSubmitCreateForm(method) {
  track({
    eventAction: 'Submit - Auth link creation',
    eventLabel: `Payment Method: ${method}`,
  });
}
