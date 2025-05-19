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
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { useNavigationLayoutContext } from '../context';
import { FALLBACK_PRODUCTS } from 'merchant/components/SidebarV2/utils/Fallback';
import {
  accountsAndSettingsIds,
  COMMON_SECTION,
  CUSTOMERS_PRODUCTS_SECTION,
} from '../NavigationContent/Payments/Sidebar/useSideNavHook';
import useSideNavigation from './useSideNavigation';
import { ANALYTICS_ONENAV } from '@libs/shared-utils';
import { useStore } from '@federated/apps/shell/commonStore';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';
import { getTitleSuffixComponent } from 'merchant/components/SidebarV2/utils/TitleSuffixRenderer';
import type { TitleSuffixConfig } from 'merchant/components/SidebarV2/utils/Products';
import type { ReactElement } from 'react';

interface ProductOptions {
  title: string;
  icon?: React.ReactNode;
  href?: string;
  routeRegex?: string;
  items?: Array<ProductOptions>;
  titleSuffix?: ReactElement | TitleSuffixConfig;
}

export interface SideNavSection {
  section_name?: string;
  section_id: string;
  max_default_options?: number;
  product_options: Array<{
    ProductOptions;
  }>;
}

type NavItemProps = {
  title: string;
  icon: IconComponent;
  href: string;
  routeRegex?: string;
  items?: Array<any>;
  titleSuffix?: ReactElement | TitleSuffixConfig;
};

type NavItemAnalyticsProps = {
  section_id?: string;
};

const MAX_VISIBLE_SECTION_ITEMS = 3; //maxVisibleItems
/**
  Note: Sidebar will be part of shell
 */

const getSection = (section_id) => {
  const navItemsToSection = {
    [COMMON_SECTION.section_id]: 'Central Products',
    [FALLBACK_PRODUCTS[0].section_id]: 'Offerings',
    [FALLBACK_PRODUCTS[1].section_id]: 'Offerings',
    [CUSTOMERS_PRODUCTS_SECTION.section_id]: 'Others',
  };
  if (accountsAndSettingsIds.has(section_id)) return 'Mode and Settings';
  return navItemsToSection[section_id] ?? '';
};

export const isItemActive = (item: any, pathname: string): boolean => {
  //Check for routeRegex match first
  if (item.routeRegex) {
    const regex = new RegExp(item.routeRegex);
    if (regex.test(pathname)) {
      return true;
    }
  }
  //Check for exact href match
  if (item.href && matchPath({ path: item.href, exact: true }, pathname)) {
    return true;
  }
  // Fallback to loose href match
  if (item.href && matchPath({ path: item.href, exact: false }, pathname)) {
    return true;
  }

  if (item.items) {
    return item.items.some((child: any) => isItemActive(child, pathname));
  }
  return false;
};

function isTitleSuffixConfig(suffix: any): suffix is TitleSuffixConfig {
  return suffix && typeof suffix === 'object' && 'componentType' in suffix;
}

export const NavItem: React.FC<NavItemProps & NavItemAnalyticsProps> = ({
  title,
  section_id,
  icon,
  href,
  items,
  routeRegex,
  titleSuffix,
}) => {
  const location = useLocation();
  const active = isItemActive({ href, routeRegex, items, icon }, location.pathname);
  const { session } = useStore();

  const { products } = useConnectedNavigationStore();

  const finalTitleSuffix = getTitleSuffixComponent(titleSuffix);

  const trackNavItemClick = () => {
    let toggleMode = '';
    const page = location.pathname?.replace(/[\/_-]/g, '');
    const { mode, partnerMode } = session;
    const alias = products.selectedProduct?.alias;

    if (alias === 'payments_top_navigation_item') {
      toggleMode = mode;
    }
    if (alias === 'partners_top_navigation_item') {
      toggleMode = partnerMode;
    }
    analyticsTrack({
      objectName: 'Sidebar',
      actionName: 'Clicked',
      screen: ANALYTICS_ONENAV.SCREEN,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        version: 'v1',
        option_name: title,
        page,
        section: section_id ? getSection(section_id) : '',
        bu_title: products.selectedProduct?.title,
        toggle_mode: toggleMode,
        experiment_name: ANALYTICS_ONENAV.EXPERIMENT_NAME,
      },
    });
  };

  if (items && items.length > 0) {
    return (
      <div onClick={trackNavItemClick}>
        <SideNavLink
          icon={icon}
          isActive={active}
          as={Link}
          title={title}
          href={items[0].href}
          titleSuffix={finalTitleSuffix}
        >
          <SideNavLevel>
            {items.map((child) => {
              const {
                items: childItems,
                title: childTitle,
                href: childHref,
                icon: childIcon,
                titleSuffix: childTitleSuffix,
              } = child;

              const childFinalTitleSuffix = getTitleSuffixComponent(childTitleSuffix);

              if (!childItems) {
                return (
                  <SideNavLink
                    as={Link}
                    isActive={isItemActive(child, location.pathname)}
                    title={childTitle}
                    href={childHref}
                    icon={childIcon}
                    titleSuffix={childFinalTitleSuffix}
                  />
                );
              }
              return (
                <SideNavLink
                  icon={childIcon}
                  isActive={active}
                  as={Link}
                  title={childTitle}
                  href={undefined}
                  titleSuffix={childFinalTitleSuffix}
                >
                  <SideNavLevel>
                    {childItems.map((subChild) => {
                      const subChildFinalTitleSuffix = getTitleSuffixComponent(
                        subChild.titleSuffix,
                      );

                      return (
                        <SideNavLink
                          as={Link}
                          isActive={isItemActive(subChild, location.pathname)}
                          title={subChild.title}
                          href={subChild.href}
                          titleSuffix={subChildFinalTitleSuffix}
                        />
                      );
                    })}
                  </SideNavLevel>
                </SideNavLink>
              );
            })}
          </SideNavLevel>
        </SideNavLink>
      </div>
    );
  }

  return (
    <div onClick={trackNavItemClick}>
      <SideNavLink
        title={title}
        as={Link}
        href={href}
        icon={icon}
        isActive={active}
        titleSuffix={finalTitleSuffix}
      />
    </div>
  );
};

const SideBar: React.FC<{ renderFullPageView: React.ReactNode }> = ({ renderFullPageView }) => {
  const { isSideNavOpenOnMobile, setIsSideNavOpenOnMobile } = useNavigationLayoutContext();
  const location = useLocation();

  const { products } = useConnectedNavigationStore();

  const { listItems, footer } = useSideNavigation(products.selectedProduct?.alias);

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
              <NavItem key={item.title} section_id={section.section_id} {...item} />
            ))}
          </SideNavSection>
        ))}
      </SideNavBody>
      {footer && <SideNavFooter>{footer}</SideNavFooter>}
    </SideNav>
  );
};

export default SideBar;
