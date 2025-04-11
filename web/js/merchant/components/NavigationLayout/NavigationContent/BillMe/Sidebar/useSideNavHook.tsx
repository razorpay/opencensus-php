import React from 'react';
import { useSideNavigationProps } from 'merchant/components/NavigationLayout/SideNavigation/useSideNavigation';

import SidebarFooter from './components/SidebarFooter';
import { BILL_ME_SIDE_NAV_SECTION, BILL_ME_SIDE_NAV_FOOTER_SECTION } from './config';

const useSideNavHook = (): useSideNavigationProps => {
  const footer: React.ReactElement = (
    <SidebarFooter listItems={[BILL_ME_SIDE_NAV_FOOTER_SECTION]} />
  );

  return {
    listItems: [BILL_ME_SIDE_NAV_SECTION],
    banner: null,
    footer,
  };
};

export default useSideNavHook;
