import React, { lazy } from 'react';

const AlertTriangleIcon = lazy(() =>
  import('@razorpay/blade/components').then((module) => ({
    default: module.AlertTriangleIcon,
  })),
);

const CheckCircleIcon = lazy(() =>
  import('@razorpay/blade/components').then((module) => ({
    default: module.CheckCircleIcon,
  })),
);

const BellIcon = lazy(() =>
  import('@razorpay/blade/components').then((module) => ({
    default: module.BellIcon,
  })),
);

const InfoIcon = lazy(() =>
  import('@razorpay/blade/components').then((module) => ({
    default: module.InfoIcon,
  })),
);

export const carouselDataWidgetIconMap = {
  critical: (): JSX.Element => (
    <AlertTriangleIcon color="feedback.icon.negative.intense" size="large" />
  ),
  failed: (): JSX.Element => (
    <AlertTriangleIcon color="feedback.icon.negative.intense" size="large" />
  ),
  need_clarification: () => (
    <AlertTriangleIcon color="feedback.icon.negative.intense" size="large" />
  ),
  closed: (): JSX.Element => (
    <CheckCircleIcon color="feedback.icon.positive.intense" size="large" />
  ),
  open: (): JSX.Element => <BellIcon color="feedback.icon.neutral.intense" size="large" />,
  info: (): JSX.Element => <InfoIcon color="feedback.icon.neutral.intense" size="large" />,
};
