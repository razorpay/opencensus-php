import { useMemo, useState, useEffect } from 'react';
import useConnectedProducts from '../hooks/useConnectedProducts';
import usePaymentsSideNavHook from '../NavigationContent/Payments/Sidebar/useSideNavHook';
import usePartnersSideNavHook from '../NavigationContent/Partner/Sidebar/useSideNavHook';
import type { IconComponent } from '@razorpay/blade/components';
import { ProductAlias } from '../typings/component';

export type SideNavSectionList = {
  section_name?: string;
  section_id: string;
  max_default_options: number;
  product_options: Array<{
    href: string;
    icon: IconComponent;
    title: string;
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
  const { getProductAction } = useConnectedProducts();
  const partnersSideNav = usePartnersSideNavHook();
  const paymentsSideNav = usePaymentsSideNavHook();

  const sideNavDataMap = useMemo(
    () => ({
      partners_top_navigation_item: partnersSideNav,
      payments_top_navigation_item: paymentsSideNav,
    }),
    [partnersSideNav, paymentsSideNav],
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
