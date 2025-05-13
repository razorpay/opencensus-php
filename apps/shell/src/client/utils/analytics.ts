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
          /* identify traits for x in one-dashboard */
          mid: window.rzp_user?.current ?? '',
          email: window.rzp_user?.email ?? '',
          name: window.rzp_user?.name ?? '',
          phone: window.rzp_user?.contact_mobile ?? '',
          business_category: window.rzp_user?.business_category ?? '',
        });
      }
    }
  });
};
