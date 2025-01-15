import { analyticsTrack } from 'common/utils/analytics';

interface HandleRefreshClickParams {
  refetch: () => void;
  setGuestToken: (arg0: string | null) => void;
  guestToken: string | null;
  embedSupersetDashboard: () => void;
}

export const handleRefreshClick = ({
  refetch,
  setGuestToken,
  guestToken,
  embedSupersetDashboard,
}: HandleRefreshClickParams) => {
  refetch();
  setGuestToken(guestToken);
  embedSupersetDashboard();
  analyticsTrack({
    objectName: 'InsightX',
    actionName: 'Refresh Button Clicked',
    screen: 'insightx/root',
  });
};
