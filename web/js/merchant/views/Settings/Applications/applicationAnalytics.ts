import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

interface Application {
  name: string;
  id: number;
  client_details: Record<string, any>;
  created_at: number;
  merchant_id: string;
  resourceField: string;
  resourceUrl: string;
  type: string;
}

// Passing entire application here as we might need to track more properties in future
export const trackApplicationActions = (application: Application) => {
  return analyticsTrackWithUserInfo({
    screen: 'Partnership',
    action: 'Clicked',
    objectName: 'Revoke access Cta',
    properties: {
      applicationName: application.name,
      appID: application.id,
      createdOn: application.created_at,
    },
  });
};
