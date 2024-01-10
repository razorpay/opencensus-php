import React, { useEffect, useRef, useState } from 'react';
import { Box, Divider, Heading, Link, Spinner, Title } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useParams } from 'react-router-dom';

import ProductFeatureTable from 'merchant/views/POS/Catalog/ProductFeatureTable';
import PosBreadcrumbs from 'merchant/views/POS/PosBreadcrumbs';
import ProductGallery from 'merchant/views/POS/ProductDescription/ProductGallery/ProductGallery';
import ProductGalleryCarousel from 'merchant/views/POS/ProductDescription/ProductGallery/ProductGalleryCarousel';
import { PAGE_READ_SUCCESS_MS, PRODUCT_DESCRIPTIONS } from 'merchant/views/POS/constants';
import { useBladeBreakpoints, useExecuteAfterDelay } from 'merchant/views/POS/hooks';
import { MainContainer } from 'merchant/views/POS/styles';
import { PricingTypes } from 'merchant/views/POS/types';
import { useScrollObserver } from 'merchant/views/POS/utils/ScrollObserver';

import DeliveryInfo from './DeliveryInfo';
import DetailedPricing from './DetailedPricing';
import PdpActions from './PdpActions';
import ProductFeaturesGallery from './ProductFeaturesGallery';
import ProductInfoBanner from './ProductInfoBanner';
import ProductPriceCards from './ProductPriceCards';
import ProductTechnicalSpecs from './ProductTechnicalSpecs';
import TermsAndConditions from './TermsAndConditions';

type ProductDescription = {
  productName: string;
};

const ProductDescription = (): JSX.Element => {
  const { productName } = useParams();
  const containerRef = useRef<HTMLElement | null>(null);
  const pricingTncRef: { current: HTMLElement | null } = useRef<HTMLElement | null>(null);
  const [selectedPricing, setSelectedPricing] = useState<PricingTypes>('monthly');

  const handlePageReadSuccess = () => {
    // Trigger pageReadSuccess event for page viewed after 15 seconds
    analytics.track_EXPERIMENTAL(SignUpEvents.pageReadSuccess, {
      pageType: 'POS PDP',
    });
  };

  useExecuteAfterDelay({ callback: handlePageReadSuccess, delay: PAGE_READ_SUCCESS_MS });

  const heroSectionRef = React.useRef<HTMLDivElement>(null);

  useScrollObserver(heroSectionRef, () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageSectionViewed, {
      sectionNumber: 1,
      section: 'Hero Section',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Product Description',
    });
  });

  useEffect(() => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageViewed, {
      pageType: 'POS PDP',
      orderId: '',
    });

    window.scrollTo(0, 0);
  }, []);

  const { matchedBreakpoint, isMobile } = useBladeBreakpoints();

  if (!productName || !PRODUCT_DESCRIPTIONS?.[productName]) {
    return (
      <Box display="flex" alignItems="center" justifyContent="center" minHeight="400px">
        <Spinner accessibilityLabel="Product Description Loader" size="xlarge" />
      </Box>
    );
  }
  const productDetails = PRODUCT_DESCRIPTIONS?.[productName];

  const handleOnPricingPlanChange = (type) => setSelectedPricing(type);

  const { productTitle, description, featureGallery, infoBanner, technicalSpecifications, code } =
    productDetails;

  const onViewPricingClick = () => {
    pricingTncRef.current?.scrollIntoView({
      behavior: 'smooth',
      block: 'start',
      inline: 'nearest',
    });
    analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
      label: 'View Pricing & TnC',
      whatsAppUpdates: 'No',
      section: 'Device',
      subSection: productTitle,
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Product Description',
    });
  };

  return (
    <React.Fragment>
      <MainContainer isIgnoreMarginBottom>
        <Box display={{ base: 'block', xl: 'flex' }} flexDirection="column" alignItems="center">
          <Box width="100%" maxWidth="1200px" ref={containerRef}>
            <PosBreadcrumbs />
            <Box
              ref={heroSectionRef}
              display="flex"
              flexDirection={matchedBreakpoint === 'xl' ? 'row' : 'column'}
              gap="spacing.5"
            >
              {isMobile ? (
                <ProductGalleryCarousel
                  gallery={productDetails.gallery}
                  productTitle={productTitle}
                />
              ) : (
                <ProductGallery gallery={productDetails.gallery} productTitle={productTitle} />
              )}
              <Box>
                <Title size="large" textAlign={isMobile ? 'center' : 'left'} testID="pdp-title">
                  {productTitle}
                </Title>
                <Box paddingTop="4px" marginBottom="spacing.5">
                  <Heading
                    size="medium"
                    color="surface.text.subtle.lowContrast"
                    weight="regular"
                    textAlign={isMobile ? 'center' : 'left'}
                  >
                    {description}
                  </Heading>
                </Box>
                <ProductPriceCards
                  productCode={code}
                  selectedPricing={selectedPricing}
                  onPricingPlanChange={handleOnPricingPlanChange}
                />
                <Link marginY="spacing.4" onClick={onViewPricingClick}>
                  View Pricing & TnC
                </Link>
                <Divider marginBottom="spacing.5" />
                <DeliveryInfo productTitle={productTitle} />
                <PdpActions
                  productCode={code}
                  plan={selectedPricing}
                  containerRef={containerRef.current}
                />
              </Box>
            </Box>
            <ProductFeaturesGallery featureGallery={featureGallery} />
            <ProductInfoBanner infoBanner={infoBanner} />
            <ProductTechnicalSpecs
              technicalSpecifications={technicalSpecifications}
              collapsibleIndex={4}
              productTitle={productTitle}
            />
            <ProductFeatureTable
              instrumentation={{
                section: 'Device Comparison',
                subSection: productTitle,
                l1FunnelStage: 'Device Exploration',
                l2FunnelStage: 'POS Product Description',
              }}
            />
          </Box>
        </Box>
      </MainContainer>
      <Box
        display="flex"
        flexDirection="column"
        alignItems="center"
        marginX={!isMobile ? 'spacing.5' : 'spacing.0'}
        paddingTop="55px"
        ref={pricingTncRef}
      >
        <DetailedPricing />
        <TermsAndConditions />
      </Box>
    </React.Fragment>
  );
};

export default ProductDescription;
