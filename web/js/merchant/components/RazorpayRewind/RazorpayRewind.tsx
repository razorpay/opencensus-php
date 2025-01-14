import React, { useEffect, useState, Suspense } from 'react';
import { Box, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';

import { BannerBtn, StyledTextHeading, StyledTextHighlight } from './styled';
import { getSlides, trackPaymentsRecapEvent, usePaymentsRecap } from './utils';

import RzpDesktopBanner from 'assets/razorpay_rewind/desktop-banner.png';
import RzpMobileBanner from 'assets/razorpay_rewind/mobile-banner.png';
import { useNavigate, useLocation, useSearchParams } from 'react-router-dom';
import { CarouselSlides } from './types';
import lazy from 'merchant/routes/LazyLoader';
import Loader from 'common/components/Loader';

const RewindModal = lazy(
  () =>
    import(/* webpackChunkName: 'rewind-modal' */ 'merchant/components/RazorpayRewind/RewindModal'),
);

const RazorpayRewind: React.FC<{
  isRtux: boolean;
}> = ({ isRtux = false }) => {
  const { isLoading, isError, data } = usePaymentsRecap();
  const [slides, setSlides] = useState<CarouselSlides[]>([]);
  const [isOpen, setIsOpen] = useState<boolean>(false);
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedBreakpoint && ['base', 'xs'].includes(matchedBreakpoint);
  const isTablet = matchedBreakpoint && ['s'].includes(matchedBreakpoint);
  const isSmallMobile = window && window.innerWidth <= 400;
  const isMobileorTablet = isMobile || isTablet;

  const isNativeWebShare = !!navigator.canShare;
  const bannerShownByDefault = localStorage.getItem('razorpay_rewind_banner');

  const location = useLocation();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const isRazorpayRewind = searchParams.get('event') === 'razorpay_rewind';

  useEffect(() => {
    if (isRtux && location.pathname === '/dashboard' && isRazorpayRewind) {
      setIsOpen(true);
      trackPaymentsRecapEvent({
        objectName: 'RZP Rewind Banner',
        actionName: 'Clicked',
      });
    }
  }, [location]);

  useEffect(() => {
    if (bannerShownByDefault !== 'true') {
      setIsOpen(true);
      localStorage.setItem('razorpay_rewind_banner', 'true');
      trackPaymentsRecapEvent({
        objectName: 'RZP Rewind Modal',
        actionName: 'Viewed',
      });
    }
  }, [isOpen]);

  useEffect(() => {
    if (data) {
      const frames = getSlides({
        data,
        isMobile,
        isTablet: isTablet,
        isSmallMobile: isSmallMobile,
      });
      setSlides(frames);
    }
  }, [data, isMobile, isTablet, isSmallMobile]);

  const onModalDismiss = () => {
    setIsOpen(false);
    if (isRtux && isRazorpayRewind) {
      navigate(-1);
    }
  };

  if (isLoading || isError || !data.is_gmv) {
    return null;
  }

  return (
    <>
      {!isRtux ? (
        <Box
          display="flex"
          flexDirection={isMobileorTablet ? 'column' : 'row'}
          alignItems="center"
          justifyContent="center"
          gap={isMobileorTablet ? 'spacing.3' : 'spacing.7'}
          padding="spacing.4"
          backgroundImage={`url("${isMobileorTablet ? RzpMobileBanner : RzpDesktopBanner}")`}
          backgroundRepeat="no-repeat"
          backgroundSize="cover"
          backgroundPosition={isMobileorTablet ? '0px calc(100% + 10px)' : 'center center'}
          borderRadius="medium"
          marginX={isMobileorTablet ? 'spacing.5' : 'spacing.6'}
        >
          <Box display="flex" flexDirection="row" alignItems="center">
            <StyledTextHeading color={theme.colors.interactive.text.staticWhite.normal}>
              We Captured Your Quarter At Razorpay {isMobileorTablet && <br />}
              <StyledTextHighlight>IN&nbsp;A&nbsp;SNAPSHOT</StyledTextHighlight>
            </StyledTextHeading>
          </Box>
          <Box padding="spacing.1">
            <BannerBtn
              onClick={() => {
                setIsOpen(true);
                trackPaymentsRecapEvent({
                  objectName: 'RZP Rewind Banner',
                  actionName: 'Clicked',
                });
              }}
              isMobileBanner={isMobileorTablet}
            >
              Check it out now!
            </BannerBtn>
          </Box>
        </Box>
      ) : null}
      {isOpen ? (
        <Suspense fallback={<Loader />}>
          <RewindModal
            isOpen={isOpen}
            onDismiss={onModalDismiss}
            slides={slides}
            isMobile={isMobile}
            isNativeWebShare={isNativeWebShare}
            isTablet={isTablet}
          />
        </Suspense>
      ) : null}
    </>
  );
};

export default RazorpayRewind;
