import { getExpiresIn } from './utils';
import { trackPill } from './analytics';
import { PILL_VARIENTS } from './constants';

export const trackEvent = ({ action, variant, latestApplication }) => {
  const screen = window?.location?.pathname?.includes?.('settlements')
    ? 'PG Dashboard | Settlements'
    : 'PG Home';
  const expiresIn = getExpiresIn(latestApplication?.created_at);
  const commonProperties = {
    screen,
    action,
  };

  switch (variant) {
    case PILL_VARIENTS.PRE_APPLICATION: {
      trackPill({
        ...commonProperties,
        variant: 'Need more money?',
      });
      break;
    }

    case PILL_VARIENTS.PROGRESS_APPLICATION: {
      trackPill({
        ...commonProperties,
        variant: 'Few steps remaining',
        properties: {
          application_status: latestApplication?.status,
          days_to_application_expiry: expiresIn,
        },
      });
      break;
    }

    default:
      break;
  }
};
