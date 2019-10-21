import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Subscription - Auth Creation';

export const track = setTrackData({
  eventCategory,
});

export function trackClickResubmitNachForm() {
  track({
    eventAction: `Click - Resubmit NACH Form`,
  });
}

export function trackClickDownloadNACHForm(status) {
  track({
    eventAction: `Details View - View NACH Form`,
    eventLabel: status,
  });
}
