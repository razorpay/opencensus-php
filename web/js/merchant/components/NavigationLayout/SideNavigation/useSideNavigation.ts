import { useMemo, useState, useEffect } from 'react';
import useConnectedProducts from '../hooks/useConnectedProducts';
import usePaymentsSideNavHook from '../NavigationContent/Payments/Sidebar/useSideNavHook';
import usePartnersSideNavHook from '../NavigationContent/Partner/Sidebar/useSideNavHook';
import useBillMeSideNavHook from '../NavigationContent/BillMe/Sidebar/useSideNavHook';
import getRizeSideTab from '../NavigationContent/Rize/SideBar/rizeSideNavHook';
import type { IconComponent } from '@razorpay/blade/components';
import { ProductAlias } from '../typings/component';
import { isBillMeOnlyMerchant } from 'merchant/utils/omniUtils';
import { useSplitzService } from 'common/splitz';

export type SideNavSectionList = {
  section_name?: string;
  section_id: string;
  max_default_options?: number;
  product_options: Array<{
    href: string;
    icon: IconComponent;
    title: string;
    product_id?: string;
    routeRegex: string | undefined;
  }>;
};

export type SideNavFooter = React.ReactElement | null;
export type SideNavBanner = React.ReactElement | null;

export interface useSideNavigationProps {
  listItems: Array<SideNavSectionList>;
  footer: SideNavFooter;
  banner: SideNavBanner;
}

const useSideNavigation = (productAlias: ProductAlias): useSideNavigationProps => {
  const splitz = useSplitzService();
  const { getProductAction } = useConnectedProducts();

  const partnersSideNav = usePartnersSideNavHook();
  const paymentsSideNav = usePaymentsSideNavHook();
  const billMeSideNav = useBillMeSideNavHook();
  const paymentSelectedSideNav = isBillMeOnlyMerchant(splitz) ? billMeSideNav : paymentsSideNav;
  const rizeSideNav = getRizeSideTab();

  const sideNavDataMap = useMemo(
    () => ({
      company_registration_top_navigation_item: rizeSideNav,
      partners_top_navigation_item: partnersSideNav,
      payments_top_navigation_item: paymentSelectedSideNav,
    }),
    [partnersSideNav, paymentsSideNav, rizeSideNav],
  );

  // If productAlias is not defined, return empty sideNav immediately
  if (!productAlias) {
    return {
      listItems: [],
      footer: null,
      banner: null,
    };
  }

  let type = 'none';

  const actionResult = getProductAction(productAlias);
  if (actionResult && actionResult.type) {
    type = actionResult.type;
  }

  if (type === 'growth_page' || type === 'access_denied_page') {
    return {
      listItems: [],
      footer: null,
      banner: null,
    };
  }

  const selectedSideNav = sideNavDataMap[productAlias] || {
    listItems: [],
    footer: null,
    banner: null,
  };

  if (!sideNavDataMap[productAlias]) {
    console.warn(`No sideNavHook defined for product alias: ${productAlias}`);
  }

  return selectedSideNav;
};

export default useSideNavigation;
