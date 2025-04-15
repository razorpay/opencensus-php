import React, { useEffect, useState, Suspense } from 'react';
import { Box, useTheme, Button } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';

import { BannerText, BannerWrapper } from './styled';
import {
  addFontLinkIfMissing,
  getSlides,
  trackPaymentsRecapEvent,
  usePaymentsRecap,
} from './utils';

import RewindLogo from 'assets/razorpay_rewind/rewind-logo.png';
import { useNavigate, useLocation, useSearchParams } from 'react-router-dom';
import { CarouselSlides } from './types';
import lazy from 'merchant/routes/LazyLoader';
import Loader from 'common/components/Loader';

export enum RewindFonts {
  TasaOrbiter = 'tasa-orbiter-font',
  DMSerif = 'dm-serif-text',
  SnugSharp = 'snug-sharp-variable',
}

export const RewindFontMapping = {
  [RewindFonts.TasaOrbiter]: `'TASA Orbiter Display', sans-serif`,
  [RewindFonts.DMSerif]: `"DM Serif Text", serif`,
  [RewindFonts.SnugSharp]: `"snug-sharp-variable",sans-serif`,
};

export const SnugSharpFontVariants = {
  BOLD: "'XHGT' 100, 'wdth' 100, 'wght' 700",
  X_BOLD: "'XHGT' 100, 'wdth' 100, 'wght' 800",
  L_NORMAL: "'XHGT' 1, 'wdth' 100, 'wght' 350",
  L_MEDIUM: "'XHGT' 1, 'wdth' 100, 'wght' 500",
};

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
  const isTabletModified = matchedBreakpoint && ['m', 'l'].includes(matchedBreakpoint);
  const isSmallMobile = window && window.innerWidth <= 400;
  const isMobileorTablet = isMobile || isTablet;
  const isMobileorTabletModified = isMobileorTablet || isTabletModified;

  const isNativeWebShare = !!navigator.canShare;
  const bannerShownByDefault = localStorage.getItem('razorpay_rewind_banner');

  const location = useLocation();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const isRazorpayRewind = searchParams.get('event') === 'razorpay_rewind';

  useEffect(() => {
    addFontLinkIfMissing(
      RewindFonts.TasaOrbiter,
      'https://fonts.cdnfonts.com/css/tasa-orbiter-display',
    );
    addFontLinkIfMissing(
      RewindFonts.DMSerif,
      'https://fonts.googleapis.com/css2?family=DM+Serif+Text:ital@0;1&display=swap',
    );
    addFontLinkIfMissing(RewindFonts.SnugSharp, 'https://use.typekit.net/inz8zop.css');
  }, []);

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
        <BannerWrapper isMobileorTablet={isMobileorTablet} isMobile={isMobile} isTablet={isTablet}>
          {!isMobileorTabletModified ? (
            <>
              <BannerText>A look back at your year of wins and highs!</BannerText>
              <img src={RewindLogo} alt="Rewind Logo" style={{ maxHeight: 70 }} />
            </>
          ) : (
            <Box>
              <img
                src={RewindLogo}
                alt="Rewind Logo"
                style={{ maxHeight: 70, marginTop: isMobile ? 70 : undefined }}
              />
              <BannerText isMobileorTabletModified>
                A look back at your year of wins and highs!
              </BannerText>
            </Box>
          )}
          <Button
            type="button"
            variant="primary"
            color="white"
            onClick={() => {
              setIsOpen(true);
              trackPaymentsRecapEvent({
                objectName: 'RZP Rewind Banner',
                actionName: 'Clicked',
              });
            }}
          >
            Check it out now!
          </Button>
        </BannerWrapper>
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
