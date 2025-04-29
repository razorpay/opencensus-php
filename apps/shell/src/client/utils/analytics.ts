import { initAnalytics } from '@libs/shared-utils';

export const initOneDashboardAnalytics = () => {
  initAnalytics().then(() => {
    if (!Boolean(window?.analytics)) {
      console.error('Segment analytics is not available');
      return;
    }

    /**
     * Individual micro-apps can call analytics.identify method which will merge the traits/properties related to the userId
     */
    if (Boolean(window?.analytics?.identify)) {
      const userId = window.rzp_user?.user?.id;

      if (userId) {
        window?.analytics?.identify?.(userId, {
          id: userId,
          userId,
          app: 'ConnectedDashboard',
        });
      }
    }
  });
};
