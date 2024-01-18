import React, { useContext } from 'react';
import { BladeProvider, Box } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { useNavigate } from 'react-router-dom';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import MainBannerBackdropImage from 'assets/pos/main-banner/mainbannerbackground.webp';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getPricingByProduct, getProductFromProductDescriptions } from 'merchant/views/POS/helpers';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';

import MainBannerProductImage from './MainBannerProductImage';
import MainBannerTextContent from './MainBannerTextContent';
import MainBannerTilesGroup from './MainBannerTilesGroup';
import { useScrollObserver } from 'merchant/views/POS/utils/ScrollObserver';
import { ANDROID_SMART_POS } from 'merchant/views/POS/constants';

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

  const { monthly, setupFee } = getPricingByProduct({ productDescription });

  const handleNavigateToProduct = () => navigate(`/pos/catalog/${productDescription.code}`);

  return (
    <BladeProvider themeTokens={paymentTheme} colorScheme="dark">
      <div data-testid="main-banner-wrapper" onClick={() => isMobile && handleNavigateToProduct()}>
        <Box
          ref={foldRef}
          borderRadius="large"
          position="relative"
          overflow="hidden"
          width="100%"
          display="flex"
          justifyContent="center"
          backgroundColor="surface.background.level3.lowContrast"
          marginBottom="spacing.5"
        >
          <Box
            display="flex"
            flexDirection="column"
            alignItems="center"
            position="relative"
            width={{ base: '100%', l: 'fit-content' }}
          >
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
                monthlyFee={monthly}
                setupFee={setupFee}
                onLearnMoreClick={() => {
                  analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
                    label: 'Learn More',
                    whatsAppUpdates: 'No',
                    l1FunnelStage: 'Device Exploration',
                    l2FunnelStage: 'POS Catalog',
                    section: 'Devices',
                    subSection: 'Android Smart POS',
                  });

                  handleNavigateToProduct();
                }}
              />
              <MainBannerProductImage price={monthly} />
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
