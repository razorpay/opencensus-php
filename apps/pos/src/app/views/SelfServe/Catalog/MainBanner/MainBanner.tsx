import React, { useContext } from 'react';
import { BladeProvider, Box } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import MainBannerBackdropImage from 'assets/pos/main-banner/mainbannerbackground.webp';
import { useNavigate } from 'react-router-dom';
import OfferStrip from 'apps/pos/src/app/views/SelfServe/Catalog/OfferStrip';
import { ANDROID_SMART_POS } from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import {
  getPricingByProduct,
  getProductFromProductDescriptions,
} from 'apps/pos/src/app/views/SelfServe/helpers';
import { useBladeBreakpoints } from 'apps/pos/src/app/views/SelfServe/hooks';
import { useScrollObserver } from 'apps/pos/src/app/views/SelfServe/utils/ScrollObserver';
import { getCommonAnalyticsProperties, analyticsTrack } from '@libs/shared-utils';
import MainBannerProductImage from './MainBannerProductImage';
import MainBannerTextContent from './MainBannerTextContent';
import MainBannerTilesGroup from './MainBannerTilesGroup';

const MainBanner = (): JSX.Element | null => {
  const navigate = useNavigate();
  const { state } = useContext(PosDeviceStoreContext);
  const { isMobile, matchedBreakpoint } = useBladeBreakpoints();
  const foldRef = React.useRef<HTMLDivElement>(null);

  const { productDescriptions } = state;

  const productDescription = getProductFromProductDescriptions({
    code: ANDROID_SMART_POS.code,
    productDescriptions,
  });

  useScrollObserver(foldRef, () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageSectionViewed, {
      sectionNumber: 1,
      section: 'Main Banner',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Catalog',
    });
  });

  if (!productDescription || !productDescription.pricing) return null;

  const productPricing = getPricingByProduct({ productDescription });
  const { monthly, offer } = productPricing;

  const handleNavigateToProduct = () => navigate(`/pos/catalog/${productDescription.code}`);

  const isPartnerPricing = productDescription?.isPartnerPricing;

  return (
    <BladeProvider themeTokens={bladeTheme} colorScheme="dark">
      <div data-testid="main-banner-wrapper" onClick={() => isMobile && handleNavigateToProduct()}>
        <Box
          ref={foldRef}
          borderRadius="large"
          position="relative"
          overflow="hidden"
          width="100%"
          display="flex"
          justifyContent="center"
          backgroundColor="surface.background.gray.moderate"
          marginBottom="spacing.5"
        >
          <Box
            display="flex"
            flexDirection="column"
            alignItems="center"
            position="relative"
            width={{ base: '100%', l: 'fit-content' }}
          >
            {productDescription?.offer ? (
              <Box
                width="100%"
                maxWidth="1300px"
                marginTop="spacing.6"
                paddingX={{ base: '0px', l: 'spacing.8' }}
                marginX={{ base: '0px', l: 'spacing.8' }}
              >
                <OfferStrip
                  text={
                    isPartnerPricing
                      ? productDescription.offer.partnerOfferText
                      : productDescription.offer.offerText
                  }
                  type="dark"
                  isPartnerPricing={isPartnerPricing}
                />
              </Box>
            ) : null}
            <Box
              display={{ base: 'flex', l: 'grid' }}
              flexDirection="column"
              alignItems="center"
              width="100%"
              paddingY="spacing.0"
              paddingX="spacing.5"
              zIndex={1}
              maxWidth="1300px"
              gridTemplateColumns={{ l: '2fr 1fr', xl: '2fr 1fr 0.5fr' }}
            >
              <MainBannerTextContent
                product={productDescription}
                productPricing={productPricing}
                onLearnMoreClick={() => {
                  analyticsTrack({
                    objectName: 'Icon',
                    actionName: 'Clicked',
                    screen: 'POS Landing Page',
                    toCleverTap: true,
                    properties: {
                      label: 'Learn More',
                      l1FunnelStage: 'Device Exploration',
                      l2FunnelStage: 'POS Catalog',
                      section: 'Devices',
                      subSection: 'Android Smart POS',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });

                  handleNavigateToProduct();
                }}
              />
              <MainBannerProductImage
                price={
                  productDescription?.offer && offer && offer?.nextMonthly !== null
                    ? offer.nextMonthly
                    : monthly
                }
                prevPrice={offer?.prevMonthly}
                isPartnerPricing={isPartnerPricing}
              />
              <MainBannerTilesGroup />
            </Box>
            <Box
              position="absolute"
              maxWidth="1200px"
              width="100%"
              height="100%"
              backgroundPosition={isMobile || matchedBreakpoint === 'm' ? 'center' : '120px 54%'}
              backgroundSize="cover"
              backgroundRepeat="no-repeat"
              backgroundImage={`url("${MainBannerBackdropImage}")`}
            />
          </Box>
        </Box>
      </div>
    </BladeProvider>
  );
};

export default MainBanner;
