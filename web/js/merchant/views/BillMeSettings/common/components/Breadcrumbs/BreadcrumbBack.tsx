import React from 'react';
import { Link, ArrowLeftIcon } from '@razorpay/blade/components';
import { useLinkClickHandler } from 'react-router-dom';

import type { BreadcrumbBackProps } from 'merchant/views/BillMeSettings/common/components/Breadcrumbs/types';

const BreadcrumbBack = ({
  children = 'Back',
  to = '../',
  icon = ArrowLeftIcon,
  relative = 'path',
  size,
  variant,
}: BreadcrumbBackProps): React.ReactElement => {
  const navigate = useLinkClickHandler(to, {
    relative,
  });
  return (
    <Link
      icon={icon}
      onClick={(e): void => navigate(e as React.MouseEvent<HTMLAnchorElement, MouseEvent>)}
      size={size}
      variant={variant}
    >
      {children}
    </Link>
  );
};

export default BreadcrumbBack;
