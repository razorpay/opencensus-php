import React from 'react';
import ProductSection from './ProductSection';
import { ISampleProducts } from './types';
import { sampleProduct } from './utils';

const _sampleProducts = [sampleProduct];

const SampleProducts = ({
  removeProduct,
  isMobile,
  children,
}: ISampleProducts): React.ReactElement | null => {
  return (
    <ProductSection
      data={_sampleProducts}
      title="Sample Product"
      className="sample-products-section"
      removeProduct={removeProduct}
      isMobile={isMobile}
    >
      {children}
    </ProductSection>
  );
};

export default SampleProducts;
