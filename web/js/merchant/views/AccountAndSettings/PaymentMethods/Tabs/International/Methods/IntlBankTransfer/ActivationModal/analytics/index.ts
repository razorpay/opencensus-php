import {
  track as trackIntl,
  AnalyticsAction,
  AnalyticsProperties,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/analytics/track';

export const track = (actionName: AnalyticsAction, properties: AnalyticsProperties): void => {
  trackIntl(actionName, {
    ...properties,
    objectName: `activation modal ${properties.objectName}`,
    subSection: 'modal activate international bank transfers',
  });
};
