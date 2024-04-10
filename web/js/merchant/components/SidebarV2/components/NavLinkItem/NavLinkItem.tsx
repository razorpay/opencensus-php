import React from 'react';
import { Badge, Text } from '@razorpay/blade/components';
import { withRouter } from 'common/deprecated/withRouter';

import { useSplitzService } from 'common/splitz';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import { DASHBOARD_LANDING_URL } from 'merchant/components/SidebarV2/constants/constants';
import { NavLinkItemInterface } from 'merchant/components/SidebarV2/typings';
import { getActiveTab } from 'merchant/components/SidebarV2/utils/href';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';
import { useI18Service } from 'common/i18';
import type { WithRouterProps } from 'common/deprecated/RouteComponentProps';
import SelectedSidebarBackground from 'assets/sidebar/sidebar-selected.svg';
import Image from 'common/ui/Image';

import {
  BadgeContainer,
  Icon,
  ImageStyled,
  LinkButtonItem,
  LinkItem,
  LinkItemV2,
  Typo,
} from './styled';
import { useIsRTUXHomepageEnabled } from 'merchant/containers/Home/RTUX/utils';

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
          <Icon className={`i ${icon}`} />
          <Text
            variant="body"
            weight="regular"
            size="medium"
            color={
              activeTab === product_id ? 'surface.text.gray.normal' : 'surface.text.gray.subtle'
            }
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
          <Text
            color={
              activeTab === product_id ? 'surface.text.gray.normal' : 'surface.text.gray.subtle'
            }
          >
            {title}
          </Text>
          {Tags}
        </LinkItem>
      )}
    </ShowWhen>
  );
};

export default withRouter(NavLinkItem);
