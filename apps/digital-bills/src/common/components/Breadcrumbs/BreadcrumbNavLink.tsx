import React from 'react';
import { BreadcrumbItem } from '@razorpay/blade/components';
import { matchPath, useHref, useLinkClickHandler } from 'react-router-dom';

import type { BreadcrumbNavLinkProps } from '@apps/digital-bills/src/common/components/Breadcrumbs/types';

const BreadcrumbNavLink = ({
  onClick,
  replace = false,
  state,
  target,
  to,
  relative = 'path',
  icon,
  children,
}: BreadcrumbNavLinkProps): React.ReactElement => {
  const href = useHref(to, { relative });
  const navigate = useLinkClickHandler(to, {
    replace,
    state,
    target,
    relative,
  });

  const pathname = window.location.pathname;
  const isCurrentPage = matchPath(href, pathname) !== null;
  return (
    <BreadcrumbItem
      isCurrentPage={isCurrentPage}
      href={href}
      onClick={(e): void => {
        onClick?.(e);
        e.preventDefault();
        navigate(e as React.MouseEvent<HTMLAnchorElement, MouseEvent>);
      }}
      icon={icon}
    >
      {children}
    </BreadcrumbItem>
  );
};

export default BreadcrumbNavLink;
