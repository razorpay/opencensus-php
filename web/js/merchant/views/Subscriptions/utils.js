import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import analytics from './analytics';

export function trackSearchEvent(event, { eventStartLabel, options }) {
  if (!event) return;

  if (typeof event === 'string') {
    analytics.track(`${eventStartLabel}.${event}`, options);
  } else {
    const label = getKeysSeparatedByPipe(event);
    if (label && label.length > 0) {
      analytics.track(`${eventStartLabel}.${label}`, options);
    }
  }
}
