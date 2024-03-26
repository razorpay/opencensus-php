import React, { useEffect, useRef, useState } from 'react';
import Stories from 'react-insta-stories';
import {
  Box,
  Button,
  IconButton,
  useTheme,
  BottomSheet,
  BottomSheetBody,
  Text,
  CloseIcon,
  ShareIcon,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';

import { Modal } from 'common/components/Modal';
import { getItem, setItem } from 'common/utils/localStorage';
import { loadImage } from 'common/utils/rzp-utils';
import { User } from 'common/typings';

import Frame from './Frame';
import {
  BannerBtn,
  CarouselContainer,
  PaymentsRecapContainer,
  SocialShareBottomSheet,
} from './styled';
import {
  DEFAULT_STORY_INTERVAL,
  getSlides,
  socialShare,
  trackPaymentsRecapEvent,
  usePaymentsRecap,
} from './utils';
import { captureImage } from './imageUtils';
import { CarouselSlides } from './types';
import CustomShareComponent from './ShareComponent';

import RzpRewindBannerDesktop from 'assets/razorpay-rewind/razorpay-rewind-banner-desktop.png';
import RzpMobileBanner from 'assets/razorpay-rewind/razorpay-rewind-banner-mobile.png';

const PaymentsRecap: React.FC<{
  user: User;
  showMobileBanner: boolean;
}> = ({ user, showMobileBanner }) => {
  const [slides, setSlides] = useState<CarouselSlides[]>([]);
  const currentStoryKey = useRef('');
  const { isLoading, isError, data } = usePaymentsRecap();
  const [isOpen, setIsOpen] = React.useState<boolean>(
    getItem(`showRazorpayRewind-${user?.current}`) !== 'false',
  );
  const [isBottomSheetOpen, setIsBottomSheetOpen] = useState(false);
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedBreakpoint && ['base', 'xs'].includes(matchedBreakpoint);
  const imgDimension = isMobile ? 300 : 400;

  const isNativeWebShare = !!navigator.canShare;
  const componentRef = useRef(null);

  const handleShareAction = (action) => {
    trackPaymentsRecapEvent({
      objectName: 'RZP Rewind Card Share',
      actionName: 'Clicked',
      properties: {
        cardKey: currentStoryKey.current,
        socialMediaPlatform: action,
      },
    });
    setIsBottomSheetOpen(false);
    if (['navigator-share', 'download', 'copy'].includes(action)) {
      captureImage(componentRef, imgDimension, action);
    } else {
      socialShare(action);
    }
  };

  const handleShare = () => {
    if (!isMobile || !isNativeWebShare) {
      setIsBottomSheetOpen(true);
    } else {
      handleShareAction('navigator-share');
    }
  };

  useEffect(() => {
    if (data) {
      const frames = getSlides({ data, handleShare, isMobile });
      setSlides(frames);
      frames.forEach(({ imgSrc }) => {
        loadImage(imgSrc);
      });
    }
  }, [data, isMobile]);

  if (isLoading || isError || !slides.length) {
    return null;
  }

  return (
    <>
      <Box
        height="100px"
        borderRadius="medium"
        position="relative"
        marginX="spacing.6"
        paddingTop="20px"
        elevation="highRaised"
      >
        <Box overflow="hidden" height="100%" width="100%" left="0px" position="relative">
          <img
            src={showMobileBanner ? RzpMobileBanner : RzpRewindBannerDesktop}
            width="100%"
            height="100%"
          />
          <BannerBtn
            onClick={() => {
              setIsOpen(true);
              trackPaymentsRecapEvent({
                objectName: 'RZP Rewind Banner',
                actionName: 'Clicked',
              });
            }}
            isMobileBanner={showMobileBanner}
          >
            Check it out now!
          </BannerBtn>
        </Box>
      </Box>
      <Modal
        isOpen={isOpen}
        onClose={() => {
          setIsOpen(false);
          setItem(`showRazorpayRewind-${user?.current}`, 'false');
        }}
        closeable={true}
        isDenserBackdrop={true}
      >
        <PaymentsRecapContainer>
          <CarouselContainer ref={componentRef}>
            <Stories
              stories={slides.map(({ imgOverlay, imgSrc, key, duration }) => ({
                content: () => {
                  // TODO: Fix this later
                  // eslint-disable-next-line react-hooks/rules-of-hooks
                  useEffect(() => {
                    trackPaymentsRecapEvent({
                      objectName: 'RZP Rewind Card',
                      actionName: 'Displayed',
                      properties: {
                        cardKey: key,
                      },
                    });
                    currentStoryKey.current = key;
                  }, []);
                  return (
                    <Frame
                      backgroundImageSrc={imgSrc}
                      children={imgOverlay}
                      imgDimension={`${imgDimension}px`}
                    />
                  );
                },
                key,
                duration: duration ?? DEFAULT_STORY_INTERVAL,
              }))}
              defaultInterval={DEFAULT_STORY_INTERVAL}
              width={isMobile ? '100%' : 500}
              height={isMobile ? 400 : 500}
              progressContainerStyles={{
                width: imgDimension,
                paddingRight: 0,
                paddingLeft: 0,
              }}
              storyInnerContainerStyles={{
                justifyContent: 'center',
                backgroundColor: '#000000',
              }}
              onAllStoriesEnd={() => {
                console.log('all stories ended');
              }}
              loop={true}
            />
          </CarouselContainer>
          <Box paddingY="spacing.4" display="flex" alignItems="center" justifyContent="center">
            <Box
              width={`${imgDimension}px`}
              display="flex"
              alignItems="center"
              justifyContent="center"
              gap="spacing.5"
            >
              <Button onClick={handleShare} icon={ShareIcon} iconPosition="left">
                Share
              </Button>
            </Box>
          </Box>
          <SocialShareBottomSheet>
            <BottomSheet
              isOpen={isBottomSheetOpen}
              onDismiss={() => {
                setIsBottomSheetOpen(false);
              }}
              zIndex={100000}
            >
              <BottomSheetBody padding="spacing.5">
                <Box
                  display="flex"
                  flexDirection="row"
                  justifyContent="space-between"
                  alignItems="center"
                  paddingX="spacing.5"
                >
                  <Text color="white.action.text.tertiary.default" size="large" weight="bold">
                    Share to
                  </Text>
                  <IconButton
                    icon={() => <CloseIcon color="action.icon.link.disabled" />}
                    onClick={() => setIsBottomSheetOpen(false)}
                    accessibilityLabel="share-drawer-close"
                  />
                </Box>
                <CustomShareComponent handleShareAction={handleShareAction} />
              </BottomSheetBody>
            </BottomSheet>
          </SocialShareBottomSheet>
        </PaymentsRecapContainer>
      </Modal>
    </>
  );
};

export default PaymentsRecap;
