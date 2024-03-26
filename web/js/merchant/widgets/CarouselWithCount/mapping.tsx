import React, { lazy } from 'react';

const CarouselDataWidget = lazy(() =>
  import('merchant/widgets/CarouselWithCount/subWidget/CarouselData').then((module) => ({
    default: module.CarouselDataWidget,
  })),
);

export const subWidgetKeyToComponentMapping = {
  carousel_item: (props): JSX.Element => <CarouselDataWidget {...props} />,
};
