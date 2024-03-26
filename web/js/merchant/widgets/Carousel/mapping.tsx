import React, { lazy } from 'react';

const ProductCardWidget = lazy(() =>
  import('merchant/widgets/Carousel/subWidget/ProductCard').then((module) => ({
    default: module.ProductCardWidget,
  })),
);

export const subWidgetKeyToComponentMapping = {
  carousel_product_card: (props): JSX.Element => <ProductCardWidget {...props} />,
};
