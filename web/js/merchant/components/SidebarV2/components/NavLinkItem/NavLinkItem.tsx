import React from 'react';
import ShowWhen from 'merchant/components/ShowWhen';
import { LinkItem, Icon, Typo, NewTag } from './styled';
import { analyticsTrack } from 'common/utils/analytics';
import { titleCase, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { withRouter } from 'react-router';
import { getActiveTab } from 'merchant/components/SidebarV2/utils/href';
import { DASHBOARD_LANDING_URL } from 'merchant/components/SidebarV2/constants/constants';
import { NavLinkItemInterface } from 'merchant/components/SidebarV2/typings';

const getTags = (type) => {
  return type.reduce((acc, each) => {
    switch (each) {
      case 'New': {
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
      screen: titleCase(getActiveTab(location)),
      toCleverTap: true,
      properties: {
        clickedElement: title,
        section: section ? titleCase(section) : title,
        location: 'sidebar',
        sidebar: 'v1/v2',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const Tags = getTags(tags);

  return (
    <ShowWhen additionalCondition={additionalCondition}>
      <LinkItem
        to={getHref ? getHref({ routes, user }) : routes[product_id] || DASHBOARD_LANDING_URL}
        isActive={activeTab === product_id}
        onClick={onNavLinkItemClick}
      >
        <Icon class={`i ${icon}`} />
        <Typo>{title}</Typo>
        {Tags}
      </LinkItem>
    </ShowWhen>
  );
};

export default withRouter(NavLinkItem);
