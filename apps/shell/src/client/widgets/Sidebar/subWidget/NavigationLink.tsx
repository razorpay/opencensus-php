import React from 'react';
import { Badge, SideNavLevel, SideNavLink } from '@razorpay/blade/components';
import { Link, useLocation } from 'react-router-dom';
import { NavigationLink as NavigationLinkType } from '@apps/shell/src/client/widgets/Sidebar/types';
import { IconMap } from '@apps/shell/src/client/widgets/utils';
import { getHref, isNavigationItemActive } from '@apps/shell/src/client/widgets/Sidebar/utils';

const NavigationLink = (link: NavigationLinkType) => {
  const { pathname } = useLocation();
  const getSideNavLinkProps = (link: NavigationLinkType) => {
    const hasTooltip = link.one_nav_config?.tooltip ?? '';
    const titleSuffix = link.one_nav_config?.title_suffix;
    const href = getHref(link);
    return {
      title: link.title,
      as: Link,
      icon: IconMap[link.icon],
      href,
      isActive: isNavigationItemActive(link, pathname),
      ...(hasTooltip ? { tooltip: { content: link.one_nav_config?.tooltip ?? '' } } : {}),
      ...(titleSuffix
        ? {
            // TODO: move this to a separate widget
            titleSuffix: <Badge color={titleSuffix.color}>{titleSuffix.value}</Badge>,
          }
        : {}),
    };
  };

  if (!link.components) {
    return <SideNavLink {...getSideNavLinkProps(link)} />;
  }

  return (
    <SideNavLink
      title={link.title}
      as={Link}
      icon={IconMap[link.icon]}
      href={getHref(link)}
      isActive={isNavigationItemActive(link, pathname)}
    >
      <SideNavLevel>
        {link.components.map((l2Item) => {
          if (!l2Item.components) {
            return <SideNavLink key={l2Item.id} {...getSideNavLinkProps(l2Item)} />;
          }

          return (
            <SideNavLink
              title={l2Item.title}
              as={Link}
              icon={IconMap[l2Item.icon]}
              key={l2Item.id}
              href={getHref(l2Item)}
              isActive={isNavigationItemActive(l2Item, pathname)}
            >
              <SideNavLevel>
                {l2Item.components?.map((l3Item) => {
                  return <SideNavLink key={l3Item.id} {...getSideNavLinkProps(l3Item)} />;
                })}
              </SideNavLevel>
            </SideNavLink>
          );
        })}
      </SideNavLevel>
    </SideNavLink>
  );
};

export { NavigationLink };
