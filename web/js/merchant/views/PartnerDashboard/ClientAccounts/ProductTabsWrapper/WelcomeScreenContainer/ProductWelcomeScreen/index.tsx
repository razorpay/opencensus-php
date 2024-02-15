import React from 'react';

import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import CapitalWelcomeScreen from './CapitalWelcomeScreen';
import PGWelcomeScreen from './PGWelcomeScreen';
import POSWelcomeScreen from './POSWelcomeScreen';
import RazorpayXWelcomeScreen from './RazorpayXWelcomeScreen';
type ProductWelcomeScreenProps = { productType: string };
const ProductWelcomeScreen = ({ productType }: ProductWelcomeScreenProps): JSX.Element => {
  switch (productType) {
    case PRODUCT_TYPE.PG:
      return <PGWelcomeScreen />;
    case PRODUCT_TYPE.POS:
      return <POSWelcomeScreen />;
    case PRODUCT_TYPE.CAPITAL:
      return <CapitalWelcomeScreen />;
    case PRODUCT_TYPE.X:
      return <RazorpayXWelcomeScreen />;
    default:
      return <PGWelcomeScreen />;
  }
};

export default ProductWelcomeScreen;
