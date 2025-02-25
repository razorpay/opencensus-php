import React from 'react';
import { SideNavFooter } from '@razorpay/blade/components';
import { NavigationFooter as NavigationFooterType } from '@apps/shell/src/client/widgets/Sidebar/types';
import { getSubWidget } from '@apps/shell/src/client/widgets/Sidebar/utils';

const NavigationFooter = (navigationFooter: NavigationFooterType) => (
  <SideNavFooter>
    {navigationFooter.components.map((component) => getSubWidget(component))}
  </SideNavFooter>
);

export { NavigationFooter };
