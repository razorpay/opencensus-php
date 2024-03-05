import React, { useRef } from 'react';
import { Box } from '@razorpay/blade/components';

import useViewportInElement from 'merchant/hooks/useViewportInElement';
import CompanyDetails from 'merchant/views/RizeMarketplace/Listing/components/CompanyDetails';
import {
  LargeDealCard,
  SmallDealCard,
} from 'merchant/views/RizeMarketplace/Listing/components/DealCard';
import TextDetails from 'merchant/views/RizeMarketplace/Listing/components/TextDetails';
import AnimatedBottomMobileBanner from 'merchant/views/RizeMarketplace/common/components/AnimatedBottomMobileBanner';
import Container from 'merchant/views/RizeMarketplace/common/components/Container';

import { ListingDetailsProps } from './types';

const ListingDetails = ({ product }: ListingDetailsProps): JSX.Element | null => {
  const textContentRef = useRef<HTMLElement>(null);
  const isViewportInTextContent = useViewportInElement(textContentRef);

  if (!product.data) return null;

  return (
    <>
      <Container display={{ l: 'none' }} marginTop="4.125rem">
        {product.data.deal ? (
          <SmallDealCard
            slug={product.slug}
            availLink={product.data.deal.avail_link}
            offer={product.data.deal.offer}
            couponCode={product.data.deal.coupon_code}
          />
        ) : null}
      </Container>

      <AnimatedBottomMobileBanner isVisible={isViewportInTextContent}>
        <Box backgroundColor="surface.background.level3.lowContrast">
          {product.data.deal ? (
            <SmallDealCard
              slug={product.slug}
              availLink={product.data.deal.avail_link}
              offer={product.data.deal.offer}
              couponCode={product.data.deal.coupon_code}
              isSticky
            />
          ) : null}
        </Box>
      </AnimatedBottomMobileBanner>

      <Container
        ref={textContentRef}
        display="grid"
        gridTemplateColumns={{ base: '1fr', l: '1fr 0.75fr', xl: 'auto 390px' }}
        gap="spacing.9"
        paddingTop={{ base: 'spacing.8', l: 'spacing.10' }}
        paddingBottom={{ base: 'spacing.5', l: 'spacing.10' }}
        alignItems="flex-start"
        justifyContent="space-between"
      >
        {product.data.details ? (
          <Box display="flex" flexDirection="column" gap="spacing.8" maxWidth="670px">
            <TextDetails heading="About the product" body={product.data.details.about} />
            <TextDetails heading="Features" body={product.data.details.features} />
            <TextDetails
              heading="Eligibility"
              body="All Razorpay Merchants are eligible for this deal!"
            />
            <TextDetails
              heading="How to avail this deal?"
              body={product.data.details.how_to_avail}
            />
          </Box>
        ) : null}

        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.8"
          padding="spacing.7"
          borderRadius="medium"
          backgroundColor="surface.background.level1.lowContrast"
        >
          <Box display={{ base: 'none', l: 'block' }}>
            {product.data.deal ? (
              <LargeDealCard
                slug={product.slug}
                availLink={product.data.deal.avail_link}
                offer={product.data.deal.offer}
                couponCode={product.data.deal.coupon_code}
              />
            ) : null}
          </Box>
          <CompanyDetails slug={product.slug} />
        </Box>
      </Container>

      <Container display={{ l: 'none' }} marginBottom="spacing.9">
        {product.data.deal ? (
          <SmallDealCard
            slug={product.slug}
            availLink={product.data.deal.avail_link}
            offer={product.data.deal.offer}
            couponCode={product.data.deal.coupon_code}
          />
        ) : null}
      </Container>
    </>
  );
};

export default ListingDetails;
