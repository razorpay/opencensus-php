import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Invoice';

export const track = setTrackData({
  eventCategory,
});
