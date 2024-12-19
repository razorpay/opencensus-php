import React from 'react';
import {
  SideNavBody,
  SideNav,
  SideNavLink,
  SideNavLevel,
  SideNavSection,
  SideNavFooter,
  IconComponent,
} from '@razorpay/blade/components';
import { Link, matchPath, useLocation } from 'react-router-dom';

import useConnectedNavigationStore from '../navigationStore';
import { useNavigationLayoutContext } from '../context';
import useSideNavigation from './useSideNavigation';

interface ProductOptions {
  title: string;
  icon?: React.ReactNode;
  href?: string;
  items?: Array<ProductOptions>;
}

export interface SideNavSection {
  section_name?: string;
  section_id: string;
  max_default_options?: number;
  product_options: Array<{
    ProductOptions;
  }>;
}

const MAX_VISIBLE_SECTION_ITEMS = 3; //maxVisibleItems
/**
  Note: Sidebar will be part of shell
 */

export const isItemActive = (item: any, pathname): boolean => {
  if (item.href && matchPath({ path: `${item.href}/*`, exact: false }, pathname)) {
    return true;
  }
  if (item.items) {
    return item.items.some((child: any) => isItemActive(child, pathname));
  }
  return false;
};

export const NavItem: React.FC<{
  title: string;
  icon: IconComponent;
  href: string;
  items?: Array<any>;
}> = ({ title, icon, href, items }) => {
  const location = useLocation();
  const active = isItemActive({ href, items }, location.pathname);

  //Currently we have only single level of nesting
  if (items) {
    return (
      <SideNavLevel>
        <SideNavLink icon={icon} isActive={active} as={Link} title={title}></SideNavLink>
        {/* {items.map((child) => (
          <NavItem key={child.title} {...child} />
        ))} */}
      </SideNavLevel>
    );
  }

  return <SideNavLink title={title} as={Link} href={href} icon={icon} isActive={active} />;
};

const SideBar: React.FC<{ renderFullPageView: React.ReactNode }> = ({ renderFullPageView }) => {
  const { isSideNavOpenOnMobile, setIsSideNavOpenOnMobile } = useNavigationLayoutContext();
  const location = useLocation();

  const { selectedProduct } = useConnectedNavigationStore();

  const { listItems, footer } = useSideNavigation(selectedProduct?.product?.alias);

  // Determine if a section should be expanded based on any nested active items
  const isSectionExpanded = (items: any[]): boolean =>
    items.some((item) => isItemActive(item, location.pathname));

  if (renderFullPageView || listItems.length == 0) return null; // hide Side Nav for FullPageView component

  return (
    <SideNav
      // banner={banner}
      onDismiss={() => setIsSideNavOpenOnMobile(false)}
      position="absolute"
      isOpen={isSideNavOpenOnMobile}
    >
      <SideNavBody>
        {listItems.map((section, index) => (
          <SideNavSection
            key={index}
            title={section.section_name}
            maxVisibleItems={
              section.product_options.length > MAX_VISIBLE_SECTION_ITEMS
                ? section.max_default_options
                : undefined
            }
            defaultIsExpanded={isSectionExpanded(section.product_options)}
          >
            {section.product_options.map((item) => (
              <NavItem key={item.title} {...item} />
            ))}
          </SideNavSection>
        ))}
      </SideNavBody>
      {footer && <SideNavFooter>{footer}</SideNavFooter>}
    </SideNav>
  );
};

export default SideBar;
