import React from 'react';
import { useTheme } from '@razorpay/blade/components';
import { useGetActiveProduct } from '../hooks';
import { RazorpayLogo, RazorpayXLogo } from '@libs/shared-ui';

export const ProductTopNavBrand: React.FC = () => {
  const { isBankingActive } = useGetActiveProduct();
  const { colorScheme } = useTheme();

  switch (true) {
    case isBankingActive:
      return <RazorpayXLogo colorScheme={colorScheme} />;
    default:
      return <RazorpayLogo />;
  }
};

export default ProductTopNavBrand;
