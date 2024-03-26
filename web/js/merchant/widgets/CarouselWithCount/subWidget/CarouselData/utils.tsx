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
  failed: (): JSX.Element => (
    <AlertTriangleIcon color="feedback.icon.negative.lowContrast" size="large" />
  ),
  need_clarification: () => (
    <AlertTriangleIcon color="feedback.icon.negative.lowContrast" size="large" />
  ),
  closed: (): JSX.Element => (
    <CheckCircleIcon color="feedback.icon.positive.lowContrast" size="large" />
  ),
  open: (): JSX.Element => <BellIcon color="feedback.icon.neutral.lowContrast" size="large" />,
  info: (): JSX.Element => <InfoIcon color="feedback.icon.neutral.lowContrast" size="large" />,
};
