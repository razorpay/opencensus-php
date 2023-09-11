import React from 'react';
import { Badge, Text } from '@razorpay/blade/components';
import { withRouter } from 'react-router';

import { useSplitzService } from 'common/splitz';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import { DASHBOARD_LANDING_URL } from 'merchant/components/SidebarV2/constants/constants';
import { NavLinkItemInterface } from 'merchant/components/SidebarV2/typings';
import { getActiveTab } from 'merchant/components/SidebarV2/utils/href';

import { BadgeContainer, Icon, LinkButtonItem, LinkItem, Typo } from './styled';

const CustomBadge = ({ text }: { text: string }) => {
  return (
    <BadgeContainer>
      <Badge contrast="high" fontWeight="bold" variant="positive" size="small">
        {text.toUpperCase()}
      </Badge>
    </BadgeContainer>
  );
};

const getTags = (type) => {
  return type.reduce((acc, each) => {
    switch (each) {
      case 'NEW': {
        acc.push(<CustomBadge text="new" />);
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
}: NavLinkItemInterface): JSX.Element | null => {
  const { abExperiments } = useSplitzService();
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
  };

  const Tags = getTags(tags);

  return (
    <ShowWhen additionalCondition={(user) => additionalCondition(user, abExperiments)}>
      {type === 'linkButton' ? (
        <LinkButtonItem
          to={getHref ? getHref({ routes, user }) : routes[product_id] || DASHBOARD_LANDING_URL}
          isActive={activeTab === product_id}
          onClick={onNavLinkItemClick}
        >
          <Typo>{title}</Typo>
          <i className="i i-chevron-right" />
          {Tags}
        </LinkButtonItem>
      ) : (
        <LinkItem
          to={getHref ? getHref({ routes, user }) : routes[product_id] || DASHBOARD_LANDING_URL}
          isActive={activeTab === product_id}
          onClick={onNavLinkItemClick}
        >
          <Icon className={`i ${icon}`} />
          <Text
            color={
              activeTab === product_id
                ? 'surface.text.normal.highContrast'
                : 'surface.text.normal.lowContrast'
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
