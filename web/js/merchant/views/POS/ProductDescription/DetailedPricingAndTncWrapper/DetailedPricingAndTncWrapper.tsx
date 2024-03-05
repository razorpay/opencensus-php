import React, { forwardRef, useContext } from 'react';
import { Box } from '@razorpay/blade/components';

import DetailedPricing from 'merchant/views/POS/ProductDescription/DetailedPricing/DetailedPricing';
import TermsAndConditions from 'merchant/views/POS/ProductDescription/TermsAndConditions';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getProductFromProductDescriptions } from 'merchant/views/POS/helpers';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';

type DetailedPricingAndTncWrapperProps = {
  productCode: string;
};

const DetailedPricingAndTncWrapper = forwardRef(
  ({ productCode }: DetailedPricingAndTncWrapperProps, ref): JSX.Element | null => {
    const { isMobile } = useBladeBreakpoints();
    const { state } = useContext(PosDeviceStoreContext);
    const { productDescriptions } = state;
    const product = getProductFromProductDescriptions({
      code: productCode as string,
      productDescriptions,
    });

    if (!product) return null;

    const isOfferExists = !!product?.offer;

    return (
      <Box
        display="flex"
        flexDirection={isOfferExists ? 'column-reverse' : 'column'}
        alignItems="center"
        marginX={!isMobile ? 'spacing.5' : 'spacing.0'}
        paddingTop="55px"
        ref={ref as React.RefObject<HTMLDivElement>}
      >
        <DetailedPricing product={product} />
        <TermsAndConditions
          title={isOfferExists ? 'Offer Terms & Conditions' : 'Terms & Conditions'}
          product={product}
          isShowPricing={isOfferExists}
          types={isOfferExists ? ['offer', 'normal'] : ['normal', 'nonOffer']}
        />
      </Box>
    );
  },
);

export default DetailedPricingAndTncWrapper;
