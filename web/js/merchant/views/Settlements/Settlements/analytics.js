import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const handleAnalytics = (objectName, actionName, properties = {}, screen) => {
  analyticsTrack({
    objectName,
    actionName,
    screen: `${screen ? screen : 'settlements'}`,
    properties: {
      ...properties,
      location: 'settlements',
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
};
const propertiesPayload = (type, data) => {
  let payload = {};
  if (type === 'settlement') {
    payload = {
      settlementId: data.id,
      settlementStatus: data.status,
      createdAt: data.created_at,
      fee: data.fees,
      tax: data.tax,
      utr: data.utr,
      amount: data.amount,
    };
  }
  if (type === 'payment') {
    payload = {
      paymentId: data.id,
      amount: data.amount,
      fee: data.fee,
      tax: data.tax,
      createdAt: data.created_at,
      international: data.international,
      status: data.status,
    };
  }
  return payload;
};
export { handleAnalytics, propertiesPayload };
