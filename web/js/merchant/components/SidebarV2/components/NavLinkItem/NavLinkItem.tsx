import React from 'react';
import { Badge, Text } from '@razorpay/blade/components';
import SelectedSidebarBackground from 'assets/sidebar/sidebar-selected.svg';

import { withRouter } from 'common/deprecated/withRouter';
import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import Image from 'common/ui/Image';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import { DASHBOARD_LANDING_URL } from 'merchant/components/SidebarV2/constants/constants';
import { NavLinkItemInterface } from 'merchant/components/SidebarV2/typings';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';
import { getActiveTab } from 'merchant/components/SidebarV2/utils/href';
import { useIsRTUXHomepageEnabled } from 'merchant/containers/Home/RTUX/utils';

import {
  BadgeContainer,
  Icon,
  ImageStyled,
  LinkButtonItem,
  LinkItem,
  LinkItemV2,
  Typo,
} from './styled';

import type { WithRouterProps } from 'common/deprecated/RouteComponentProps';
import { isMobileDevice } from 'merchant/components/Home/data';
import getPricingPlan from 'merchant/components/Announcements/MonetizationCharges/utils/getPricingPlan';

const CustomBadge = ({ text }: { text: string }) => {
  return (
    <BadgeContainer>
      <Badge emphasis="intense" size="small" color="positive">
        {text.toUpperCase()}
      </Badge>
    </BadgeContainer>
  );
};

const getTags = (type) => {
  return type.reduce((acc, each, index) => {
    switch (each) {
      case 'NEW': {
        acc.push(<CustomBadge text="new" key={index} />);
        break;
      }
      default:
    }
    return acc;
  }, [] as JSX.Element[]);
};

const NavLinkItem = ({
  title,
  icon,
  type,
  activeTab,
  tags = [],
  additionalCondition,
  getHref,
  routes,
  user,
  product_id,
  section,
  location,
  image,
  toggleMobileMenu,
}: NavLinkItemInterface & WithRouterProps): JSX.Element | null => {
  const { abExperiments } = useSplitzService();
  const { isConfigTagEnabled } = useI18Service();
  const extraConfig: ExtraConfig = { abExperiments, isConfigTagEnabled };
  const isRTUXHomepage = useIsRTUXHomepageEnabled();
  const pricingPlanForMerchant = getPricingPlan(user);
  title =
    user?.isC360OnboardingCompleted && product_id === 'magic_checkout' ? 'Checkout360' : title;

  const noCodeMonetizationApps = ['Payment Links', 'Payment Pages', 'Invoices', 'Razorpay.me Link'];

  const onNavLinkItemClick = () => {
    analyticsTrack({
      objectName: 'sidebar',
      actionName: 'clicked',
      screen: titleCase(getActiveTab(location)) || 'home page',
      toCleverTap: true,
      properties: {
        clickedElement: title,
        section: section ? titleCase(section) : title,
        location: 'sidebar',
        sidebar: 'v2',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    toggleMobileMenu?.();
    if (noCodeMonetizationApps.includes(title)) {
      analyticsTrack({
        objectName: 'NC App Widget',
        actionName: 'Clicked',
        screen: title,
        toCleverTap: true,
        properties: {
          event_name: 'nc_app_widget.click.initiated',
          source: getDeviceSource(),
          page: title,
          email_id: user?.email,
          url: window.location.href,
          browser: window.razorpayAnalytics?.utils?.getBrowserDetails(),
          activation_status: user?.activation_status,
          device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
          exp_name: 'NoCode Monetization',
          pricing: pricingPlanForMerchant,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  };

  const Tags = getTags(tags);
  const url: string = getHref
    ? getHref({ routes, user })
    : routes[product_id] || DASHBOARD_LANDING_URL;

  const isActive = activeTab === product_id;

  return (
    <ShowWhen additionalCondition={(user) => additionalCondition(user, extraConfig)}>
      {type === 'linkButton' ? (
        <LinkButtonItem to={url} isActive={isActive} onClick={onNavLinkItemClick}>
          <Typo>{title}</Typo>
          <i className="i i-chevron-right" />
          {Tags}
        </LinkButtonItem>
      ) : isRTUXHomepage ? (
        <LinkItemV2 to={url} onClick={onNavLinkItemClick} isActive={isActive}>
          {isActive && (
            <Image
              src={SelectedSidebarBackground}
              alt="Selected background"
              className="sidebar-active"
            />
          )}
          <Icon className={`i ${icon}`} isRTUXHomepage={isRTUXHomepage} isActive={isActive} />
          <Text
            variant="body"
            weight={isActive ? 'medium' : 'regular'}
            size="medium"
            color={isActive ? 'surface.text.gray.normal' : 'surface.text.gray.subtle'}
          >
            {title}
          </Text>
          {Tags}
        </LinkItemV2>
      ) : (
        <LinkItem
          to={getHref ? getHref({ routes, user }) : routes[product_id] || DASHBOARD_LANDING_URL}
          isActive={activeTab === product_id}
          onClick={onNavLinkItemClick}
        >
          {image ? (
            <ImageStyled height="15px" width="15px" src={image} alt={title} />
          ) : (
            <Icon className={`i ${icon}`} />
          )}
          <Text color={isActive ? 'surface.text.gray.normal' : 'surface.text.gray.subtle'}>
            {title}
          </Text>
          {Tags}
        </LinkItem>
      )}
    </ShowWhen>
  );
};

export default withRouter(NavLinkItem);
