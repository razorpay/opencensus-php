// core
import React from 'react';
import { withRouter } from 'react-router';

// analytics
import { analyticsTrack } from 'common/utils/analytics';

// utils
import { titleCase, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getActiveTab } from 'merchant/components/SidebarV2/utils/href';

// constants
import { DASHBOARD_LANDING_URL } from 'merchant/components/SidebarV2/constants/constants';

// types
import { NavLinkItemInterface } from 'merchant/components/SidebarV2/typings';

// components
import ShowWhen from 'merchant/components/ShowWhen';

// styles
import { LinkItem, Icon, Typo, NewTag, LinkButtonItem } from './styled';

const getTags = (type) => {
  return type.reduce((acc, each) => {
    switch (each) {
      case 'NEW': {
        acc.push(<NewTag>New</NewTag>);
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
    <ShowWhen additionalCondition={additionalCondition}>
      {type === 'linkButton' ? (
        <LinkButtonItem
          to={getHref ? getHref({ routes, user }) : routes[product_id] || DASHBOARD_LANDING_URL}
          isActive={activeTab === product_id}
          onClick={onNavLinkItemClick}
        >
          {title}
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
          <Typo>{title}</Typo>
          {Tags}
        </LinkItem>
      )}
    </ShowWhen>
  );
};

export default withRouter(NavLinkItem);
