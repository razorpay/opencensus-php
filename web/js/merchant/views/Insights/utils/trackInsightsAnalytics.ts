import { analyticsTrack } from 'common/utils/analytics';

type BaseAnalyticsProps = Record<string, any>;

export const trackInsightsAnalytics = (
  Insights_Dashboard: string,
  activeTab: string,
  insights_dashboard: string,
  baseAnalyticsProps: BaseAnalyticsProps,
  actionName: string,
  additionalProps: Record<string, any> = {}
) => {
  analyticsTrack({
    objectName: `Insights - ${Insights_Dashboard}`,
    actionName,
    screen: `${insights_dashboard} - ${activeTab}`,
    properties: {
      ...baseAnalyticsProps,
      ...additionalProps,
      event_name: `insights.${insights_dashboard}.${activeTab}.${actionName
        .toLowerCase()
        .replace(/\s+/g, '_')}`,
    },
  });
};
