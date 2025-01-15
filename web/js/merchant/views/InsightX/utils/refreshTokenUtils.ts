export const setupTokenRefresh = ({
  refetch,
  setGuestToken,
  embedSupersetDashboard,
  analyticsTrack,
}) => {
  const intervalId = setInterval(async () => {
    const updatedToken = await refetch();
    if (updatedToken.data) {
      setGuestToken(updatedToken.data);
      embedSupersetDashboard();
    }
    analyticsTrack({
      objectName: 'InsightX',
      actionName: 'Token Refetch after Refresh Interval',
      screen: 'insightx/root',
    });
  }, 3 * 60 * 1000);

  return intervalId;
};
