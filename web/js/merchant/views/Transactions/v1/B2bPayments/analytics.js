// analytics
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const track = ({ properties, ...args }) => {
  analyticsTrack({
    objectName: 'b2b payments',
    screen: 'transactions',
    ...args,
    properties: {
      location: 'b2b payments',
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...properties,
    },
  });
};

export const trackFilterSubmit = (properties) => {
  track({ properties, objectName: 'b2b payments search', actionName: 'result' });
};

export const trackSearchClicked = (properties) => {
  track({ properties, objectName: 'b2b payments search', actionName: 'clicked' });
};

export const trackSearchClear = (properties) => {
  track({ properties, objectName: 'b2b payments search', actionName: 'clear' });
};

export const trackInvoiceUploadClick = (properties) => {
  track({ properties, objectName: 'b2b payments invoice upload', actionName: 'clicked' });
};

export const trackInvoiceUploadStatus = (properties) => {
  track({ properties, objectName: 'b2b payments invoice upload', actionName: 'result' });
};

export const trackInvoiceViewClick = (properties) => {
  track({ properties, objectName: 'b2b payments invoice view', actionName: 'clicked' });
};

export const trackInvoiceViewStatus = (properties) => {
  track({ properties, objectName: 'b2b payments invoice view', actionName: 'result' });
};

export const trackShown = (properties) => {
  track({ properties, objectName: 'b2b payments', actionName: 'loaded' });
};
