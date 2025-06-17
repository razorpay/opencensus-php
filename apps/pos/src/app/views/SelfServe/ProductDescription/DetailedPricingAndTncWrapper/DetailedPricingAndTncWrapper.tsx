import React, { forwardRef, useContext } from 'react';
import { Box } from '@razorpay/blade/components';

import DetailedPricing from 'apps/pos/src/app/views/SelfServe/ProductDescription/DetailedPricing/DetailedPricing';
import TermsAndConditions from 'apps/pos/src/app/views/SelfServe/ProductDescription/TermsAndConditions';
import {
  DETAILED_PRICING,
  WD10_DETAILED_OFFER_PRICING,
} from 'apps/pos/src/app/views/SelfServe/constants';
import SOUNDBOX from 'apps/pos/src/app/views/SelfServe/constants/Soundbox';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import { getProductFromProductDescriptions } from 'apps/pos/src/app/views/SelfServe/helpers';
import { useBladeBreakpoints } from 'apps/pos/src/app/views/SelfServe/hooks';

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

    const getPricingDetails = () => {
      if (productCode === SOUNDBOX.code) {
        return WD10_DETAILED_OFFER_PRICING;
      }
      return DETAILED_PRICING;
    };

    return (
      <Box
        display="flex"
        flexDirection={isOfferExists ? 'column-reverse' : 'column'}
        alignItems="center"
        marginX={!isMobile ? 'spacing.5' : 'spacing.0'}
        paddingTop="55px"
        ref={ref as React.RefObject<HTMLDivElement>}
      >
        <DetailedPricing pricingDetails={getPricingDetails()} product={product} />
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
