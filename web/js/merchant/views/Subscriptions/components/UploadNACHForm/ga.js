import { setTrackData } from 'common/utils/googleAnalytics';

const eventCategory = 'Dashboard - Subscription - NACH upload';

export const track = setTrackData({
  eventCategory,
});

export function trackUploadNachFormStatus(type, error) {
  track({
    eventAction: `Upload - NACH Form (${type})`,
    eventLabel: error,
  });
}

export function trackReadNachFormStatus(type, error) {
  track({
    eventAction: `Read - NACH Form (${type})`,
    eventLabel: error,
  });
}

export function trackClickMandateDetails(isError) {
  track({
    eventAction: `Click - Mandate details`,
    eventLabel: isError ? 'Error state' : 'Success state',
  });
}

export function trackClickPersonalDetails(isError) {
  track({
    eventAction: `Click - Personal details`,
    eventLabel: isError ? 'Error state' : 'Success state',
  });
}

export function trackClickBankDetails(isError) {
  track({
    eventAction: `Click - Mandate details`,
    eventLabel: isError ? 'Error state' : 'Success state',
  });
}
