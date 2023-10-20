import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import { TrackAnalyticsType } from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/types';

const DOWNLOAD_SWIFT_COPY = 'download swift copy';

const ACTIONS = {
  clicked: 'clicked',
  response: 'response',
};

const track = ({ properties, objectName, actionName }: TrackAnalyticsType): void => {
  analyticsTrack({
    objectName,
    actionName,
    screen: 'transactions',
    properties: {
      ...getCommonSegmentProperties(),
      ...properties,
    },
  });
};

export const trackDownloadButtonClicked = (): void => {
  track({
    objectName: DOWNLOAD_SWIFT_COPY,
    actionName: ACTIONS.clicked,
  });
};

export const trackDownloadSuccess = (): void => {
  track({
    objectName: DOWNLOAD_SWIFT_COPY,
    actionName: ACTIONS.response,
    properties: {
      success: '1',
    },
  });
};

export const trackDownloadFailure = (): void => {
  track({
    objectName: DOWNLOAD_SWIFT_COPY,
    actionName: ACTIONS.response,
    properties: {
      success: '0',
    },
  });
};
