import React, { lazy } from 'react';

const Link = lazy(() =>
  import('merchant/widgets/common/Link').then((module) => ({
    default: module.LinkWidget,
  })),
);

const Button = lazy(() =>
  import('merchant/widgets/common/Button').then((module) => ({
    default: module.ButtonWidget,
  })),
);

const BaseSelect = lazy(() => import('merchant/widgets/common/BaseSelect'));

const Select = lazy(() => import('./Select'));

export const commonWidgetKeyToComponentMapping = {
  link: (props): JSX.Element => <Link {...props} />,
  button: (props): JSX.Element => <Button {...props} />,
};

export const inputKeyToComponentMapping = {
  select: (props): JSX.Element => <Select {...props} />,
  'base-select': (props): JSX.Element => <BaseSelect {...props} />,
};
