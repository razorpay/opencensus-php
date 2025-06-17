import React, { useContext, useEffect, useRef, useState } from 'react';
import { Box, Divider, Heading, Link, Spinner } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useParams } from 'react-router-dom';

import ProductFeatureTable from 'apps/pos/src/app/views/SelfServe/Catalog/ProductFeatureTable';
import PosBreadcrumbs from 'apps/pos/src/app/views/SelfServe/PosBreadcrumbs';
import ProductGallery from 'apps/pos/src/app/views/SelfServe/ProductDescription/ProductGallery/ProductGallery';
import ProductGalleryCarousel from 'apps/pos/src/app/views/SelfServe/ProductDescription/ProductGallery/ProductGalleryCarousel';
import {
  PAGE_READ_SUCCESS_MS,
  PRODUCT_DESCRIPTIONS,
} from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import {
  getInitialPlan,
  getProductFromProductDescriptions,
} from 'apps/pos/src/app/views/SelfServe/helpers';
import { useBladeBreakpoints, useExecuteAfterDelay } from 'apps/pos/src/app/views/SelfServe/hooks';
import { MainContainer } from 'apps/pos/src/app/views/SelfServe/styles';
import { PricingTypes } from 'apps/pos/src/app/views/SelfServe/types';
import { useScrollObserver } from 'apps/pos/src/app/views/SelfServe/utils/ScrollObserver';

import DeliveryInfo from './DeliveryInfo';
import DetailedPricingAndTncWrapper from './DetailedPricingAndTncWrapper';
import PdpActions from './PdpActions';
import ProductFeaturesGallery from './ProductFeaturesGallery';
import ProductInfoBanner from './ProductInfoBanner';
import ProductPriceCards from './ProductPriceCards';
import ProductTechnicalSpecs from './ProductTechnicalSpecs';

type ProductDescription = {
  productName: string;
};

const ProductDescription = (): JSX.Element | null => {
  const { productName } = useParams();
  const containerRef = useRef<HTMLElement | null>(null);
  const pricingTncRef: { current: HTMLElement | null } = useRef<HTMLElement | null>(null);
  const { state } = useContext(PosDeviceStoreContext);
  const { productDescriptions } = state;
  const [selectedPricing, setSelectedPricing] = useState<PricingTypes>(
    getInitialPlan({
      productDescription: getProductFromProductDescriptions({
        code: productName as string,
        productDescriptions,
      }),
    }),
  );
  const productDetails = getProductFromProductDescriptions({
    code: productName as string,
    productDescriptions,
  });

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

  if (!productDetails) return null;

  const handleOnPricingPlanChange = (type) => setSelectedPricing(type);

  const {
    productTitle,
    description,
    featureGallery,
    infoBanner,
    technicalSpecifications,
    code,
    shouldShowProductVarietyTable,
  } = productDetails;

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
                <Heading textAlign={isMobile ? 'center' : 'left'} testID="pdp-title" size="xlarge">
                  {productTitle}
                </Heading>
                <Box paddingTop="4px" marginBottom="spacing.5">
                  <Heading
                    color="surface.text.gray.subtle"
                    weight="regular"
                    textAlign={isMobile ? 'center' : 'left'}
                    marginBottom="spacing.8"
                    size="small"
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
            {shouldShowProductVarietyTable ? (
              <ProductFeatureTable
                instrumentation={{
                  section: 'Device Comparison',
                  subSection: productTitle,
                  l1FunnelStage: 'Device Exploration',
                  l2FunnelStage: 'POS Product Description',
                }}
              />
            ) : null}
          </Box>
        </Box>
      </MainContainer>
      <DetailedPricingAndTncWrapper productCode={code} ref={pricingTncRef} />
    </React.Fragment>
  );
};

export default ProductDescription;
