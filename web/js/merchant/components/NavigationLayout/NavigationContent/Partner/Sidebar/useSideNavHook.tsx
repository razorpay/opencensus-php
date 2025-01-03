import React, { useMemo } from 'react';
import {
  PARTNER_SIDE_NAV_SECTION,
  PARTNER_SIDE_NAV_FOOTER_SECTION,
  PARTNER_SIDENAV_POS_SALES_SECTION,
} from './config';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import { useSplitzService } from 'common/splitz';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';
import { useStore } from 'shell/commonStore';
import SidebarFooter from './components/SidebarFooter';

import {
  useSideNavigationProps,
  //SideNavFooter as SideNavFooterComponent,
} from 'merchant/components/NavigationLayout/SideNavigation/useSideNavigation';

function filterListNavItems(dataItems, { abExperiments }) {
  return dataItems
    .map((section) => {
      const productOptionsKey = 'product_options';
      const options = section[productOptionsKey] || [];

      const updatedOptions = options.reduce((acc, product) => {
        if (
          showWhenUtil({
            additionalCondition: (users) =>
              product.additionalCondition(users, {
                abExperiments,
              }),
          })
        ) {
          acc.push({
            title: product.title,
            href: product.href,
            icon: product.bladeIcon,
            routeRegex: product.routeRegex, // currently there are no regex based routing for partners, added to maintain the same consistency as that is present in payments side nav items
          });
        }
        return acc;
      }, []);

      // Return updated section only if there are matching options
      return updatedOptions.length > 0
        ? {
            ...section,
            [productOptionsKey]: updatedOptions, // Preserve the key used
          }
        : null;
    })
    .filter(Boolean); // Remove sections with no matching product options
}

const useSideNavHook = (): useSideNavigationProps => {
  const { abExperiments } = useSplitzService();
  const user = useStore((state) => state.session.user);
  const { isPosSalesAgent } = checkIfPosSalesAgent({
    user,
    abExperiments,
  });

  let sideNavSectionData = [PARTNER_SIDE_NAV_SECTION];

  if (isPosSalesAgent) {
    sideNavSectionData = [PARTNER_SIDENAV_POS_SALES_SECTION];
  }

  const listItemsData = useMemo(() => {
    return filterListNavItems(sideNavSectionData, { abExperiments });
  }, [PARTNER_SIDE_NAV_FOOTER_SECTION, abExperiments]);

  const footerSectionData = useMemo(() => {
    return filterListNavItems([PARTNER_SIDE_NAV_FOOTER_SECTION], {
      abExperiments,
    });
  }, [PARTNER_SIDE_NAV_FOOTER_SECTION, abExperiments]);

  const footer: React.ReactElement = useMemo(() => {
    return <SidebarFooter listItems={footerSectionData} />;
  }, [footerSectionData]);

  return {
    listItems: listItemsData,
    banner: null,
    footer,
  };
};

export default useSideNavHook;
