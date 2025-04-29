import React from 'react';
import { useGetActiveProduct } from '../hooks';
import { RazorpayLogo, RazorpayXLogo } from '@libs/shared-ui';

export const ProductTopNavBrand: React.FC = () => {
  const { isBankingActive } = useGetActiveProduct();

  switch (true) {
    case isBankingActive:
      return <RazorpayXLogo />;
    default:
      return <RazorpayLogo />;
  }
};

export default ProductTopNavBrand;
