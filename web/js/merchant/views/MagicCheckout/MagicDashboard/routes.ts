import KeyReportsAndAnalytics from 'merchant/views/MagicCheckout/MagicDashboard/Wrapper';
import WhatsNew from 'merchant/views/MagicCheckout/MagicDashboard/WhatsNew';
import C360ControlCenter from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter';

import { formatRoutesByPlatform } from 'merchant/views/MagicCheckout/utils/formatGenericRoutes';

import { PlatformSpecificRoutes, RouteItem } from 'merchant/views/MagicCheckout/types';
import SupportAndFeedback from 'merchant/views/MagicCheckout/MagicDashboard/SupportAndFeedback';

const GENERIC_ROUTES: RouteItem[] = [
  {
    label: 'Control Center',
    path: '/magic/dashboard/control-center',
    Component: C360ControlCenter,
    onRCODOnly: true,
    condition: ((_user, abExperiments) =>
      (_user.isC360OnboardingCompleted || _user.isC360OnboardingToBeResumed) &&
      abExperiments?.magicx_publicapp_cod?.variables?.result === 'on') as any,
  },
  {
    label: 'Key Reports and Analytics',
    path: '/magic/dashboard/key-reports',
    Component: KeyReportsAndAnalytics,
  },
  {
    label: "What's New",
    path: '/magic/dashboard/whats-new',
    Component: WhatsNew,
    onRCOD: true,
  },
  {
    label: 'Support & Feedback',
    path: '/magic/dashboard/support-feedback',
    Component: SupportAndFeedback,
    onRCODOnly: true
  },
];

//DRY - Above routes are common for all platforms
export const DEFAULT_ROUTES: PlatformSpecificRoutes = formatRoutesByPlatform(GENERIC_ROUTES);

export const PATH_PREFIX = '/magic/dashboard/';
export const REPORTS_AND_ANALYTICS_ROUTE = '/magic/reports-analytics';
