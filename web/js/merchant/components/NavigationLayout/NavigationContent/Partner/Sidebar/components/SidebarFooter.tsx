import React from 'react';
import { Box } from '@razorpay/blade/components';
import { useStore } from 'shell/commonStore';

import { NavItem } from 'merchant/components/NavigationLayout/SideNavigation/SideNavigation';
import SidebarModeSwitcher from 'merchant/components/NavigationLayout/SideNavigation/components/SidebarModeSwitcher';
import { SideNavSectionList } from 'merchant/components/NavigationLayout/SideNavigation/useSideNavigation';

interface SidebarFooterProps {
  listItems: SideNavSectionList[];
}

const SidebarFooter = ({ listItems }: SidebarFooterProps) => {
  const { partnerMode: mode } = useStore((state) => state.session);

  return (
    <Box>
      <SidebarModeSwitcher mode={mode} />
      {listItems.map((section, index) => (
        <React.Fragment key={index}>
          {section.product_options.map((item) => (
            <NavItem key={item.title} {...item} />
          ))}
        </React.Fragment>
      ))}
    </Box>
  );
};

export default React.memo(SidebarFooter);
