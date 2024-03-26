import React, { lazy } from 'react';

const Link = lazy(() =>
  import('merchant/widgets/common/Link').then((module) => ({
    default: module.LinkWidget,
  })),
);

const Select = lazy(() => import('./Select'));

export const commonWidgetKeyToComponentMapping = {
  link: (props): JSX.Element => <Link {...props} />,
};

export const inputKeyToComponentMapping = {
  select: (props): JSX.Element => <Select {...props} />,
};
