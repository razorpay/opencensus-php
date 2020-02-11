import { setTrackData } from 'common/utils/googleAnalytics';

const eventCategory = 'Dashboard - Subscription - Auth';

export const track = setTrackData({
  eventCategory,
});

export function trackOpenAuthLink() {
  track({
    eventAction: `Open Details - Auth link`,
  });
}

export function trackClickUploadNACHForm() {
  track({
    eventAction: `Click - Upload NACH form`,
  });
}

export function trackClickDownloadNACHForm() {
  track({
    eventAction: `Click - Download NACH form`,
  });
}

export function trackClickViewNACHForm() {
  track({
    eventAction: `Click - View NACH Form`,
  });
}
