// analytics
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

// types
import type {
  TrackAnalyticsType,
  TrackAnalyticsProperties,
} from 'merchant/views/Transactions/v1/UploadInvoice/types';

const track = ({ properties, ...args }: TrackAnalyticsType): void => {
  const props = Object.assign(properties ?? {}, {
    location: 'opgsp payments',
    ...getCommonAnalyticsProperties(window.rzp_user),
  });

  analyticsTrack({
    objectName: 'opgsp payments',
    screen: 'transactions',
    ...args,
    properties: props,
  });
};

export const trackFilterSubmit = (properties: TrackAnalyticsProperties): void => {
  if (typeof properties === 'object') {
    track({ properties, objectName: 'opgsp payments search', actionName: 'result' });
  }
};

export const trackSearchClicked = (properties: TrackAnalyticsProperties): void => {
  track({ properties, objectName: 'opgsp payments search', actionName: 'clicked' });
};

export const trackSearchClear = (properties: TrackAnalyticsProperties): void => {
  track({ properties, objectName: 'opgsp payments search', actionName: 'clear' });
};

export const trackInvoiceUploadClick = (properties: TrackAnalyticsProperties): void => {
  track({ properties, objectName: 'opgsp payments invoice upload', actionName: 'clicked' });
};

export const trackInvoiceUploadStatus = (properties: TrackAnalyticsProperties): void => {
  track({ properties, objectName: 'opgsp payments invoice upload', actionName: 'result' });
};

export const trackInvoiceViewClick = (properties: TrackAnalyticsProperties): void => {
  track({ properties, objectName: 'opgsp payments invoice view', actionName: 'clicked' });
};

export const trackInvoiceViewStatus = (properties: TrackAnalyticsProperties): void => {
  track({ properties, objectName: 'opgsp payments invoice view', actionName: 'result' });
};

export const trackShown = (properties: TrackAnalyticsProperties): void => {
  track({ properties, objectName: 'opgsp payments', actionName: 'loaded' });
};
