import React from 'react';
import { useGetActiveProduct } from '../hooks';
import { RazorpayLogo, RazorpayXLogo } from '@libs/shared-ui';

export const ProductTopNavBrand: React.FC = () => {
  const { isBankingActive } = useGetActiveProduct();

  // TODO: return RazorpayXLogo when launching X in connected dashboard
  switch (true) {
    case isBankingActive:
      return <RazorpayLogo />;
    default:
      return <RazorpayLogo />;
  }
};

export default ProductTopNavBrand;
