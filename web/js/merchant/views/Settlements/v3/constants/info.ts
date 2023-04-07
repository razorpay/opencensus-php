import { VALUE_TYPE } from 'merchant/views/Settlements/v3/typings';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

export const SETTLEMENT_INFO = [
  {
    id: 'amount',
    name: 'Net settlement',
    type: VALUE_TYPE.AMOUNT,
    isCopy: false,
  },
  {
    id: 'id',
    name: 'Settlement ID',
    type: VALUE_TYPE.TEXT,
    isCopy: true,
  },
  {
    id: 'utr',
    name: 'UTR number',
    type: VALUE_TYPE.TEXT,
    isCopy: true,
    onItemCopy: (additionalData: any): void => {
      analyticsTrack({
        objectName: 'Merchant copies',
        actionName: 'UTR number for a given Settlement',
        screen: 'Settlements',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user),
          page: 'Details View',
          settlements_experiment_name: 'v2',
          sessionId: window?.session_id ? window.session_id : undefined,
          ...additionalData,
        },
      });
    },
  },
  {
    id: 'created_at',
    name: 'Created on',
    type: VALUE_TYPE.DATE,
    isCopy: false,
  },
  {
    id: 'status',
    name: 'Status',
    type: VALUE_TYPE.CHIP,
    isCopy: false,
  },
];

export const VariantMap = {
  FAILED: 'negative',
  CREATED: 'notice',
  PROCESSED: 'positive',
};
