import React, { useEffect, useMemo, useRef, useState } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
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
import {
  togglePaymentsRecapBannerVisibility as togglePaymentsRecapBannerVisibilityFn,
  togglePaymentsRecapModal as togglePaymentsRecapModalFn,
} from 'merchant/reducers/paymentsRecap';

import { CarouselContainer, PaymentsRecapContainer, SocialShareBottomSheet } from './styled';
import {
  DEFAULT_STORY_INTERVAL,
  getSlides,
  getStories,
  socialShare,
  trackPaymentsRecapEvent,
  usePaymentsRecap,
} from './utils';
import { captureImage, downloadAllSlides, getImgBlob } from './imageUtils';
import { CarouselSlides } from './types';
import CustomShareComponent from './ShareComponent';

const PaymentsRecap: React.FC<{
  user: User;
  paymentsRecap: any;
  togglePaymentsRecapModal: (flag) => void;
  togglePaymentsRecapBannerVisibility: (flag) => void;
}> = ({ user, paymentsRecap, togglePaymentsRecapModal, togglePaymentsRecapBannerVisibility }) => {
  const componentRef = useRef(null);

  const { isOpen } = paymentsRecap;
  const [slides, setSlides] = useState<CarouselSlides[]>([]);
  const [capturedSlides, setCapturedSlides] = useState(new Map());

  const [currentStoryKey, setCurrentStoryKey] = useState('');

  const { isLoading, isError, data } = usePaymentsRecap(user?.current);

  const [isBottomSheetOpen, setIsBottomSheetOpen] = useState(false);
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedBreakpoint && ['base', 'xs'].includes(matchedBreakpoint);
  const imgDimension = isMobile ? 300 : 400;

  const isNativeWebShare = !!navigator.canShare;

  const handleShareAction = (action) => {
    trackPaymentsRecapEvent({
      objectName: 'RZP Rewind Card Share',
      actionName: 'Clicked',
      properties: {
        cardKey: currentStoryKey,
        socialMediaPlatform: action,
      },
    });
    setIsBottomSheetOpen(false);
    if (['navigator-share', 'copy'].includes(action)) {
      captureImage(componentRef, imgDimension, action);
    } else if (action === 'download') {
      if (capturedSlides.size) {
        downloadAllSlides(capturedSlides);
      }
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
      if (frames.length) {
        togglePaymentsRecapBannerVisibility(true);
      }
      frames.forEach(({ imgSrc }) => {
        loadImage(imgSrc);
      });
    }
  }, [data, isMobile]);

  const onStoryStart = (cardKey) => {
    trackPaymentsRecapEvent({
      objectName: 'RZP Rewind Card',
      actionName: 'Displayed',
      properties: {
        cardKey,
      },
    });
    setCurrentStoryKey(cardKey);
    if (!isMobile || !isNativeWebShare) {
      if (componentRef.current) {
        getImgBlob(componentRef).then((blob) => {
          if (blob && !capturedSlides.has(cardKey)) {
            setCapturedSlides((prevSlides) => new Map(prevSlides.set(cardKey, blob)));
          }
        });
      }
    }
  };

  const stories = useMemo(
    () =>
      getStories({
        imgDimension,
        slides,
        onStoryStart,
      }),
    [imgDimension, slides],
  );

  useEffect(() => {
    if (getItem(`showRazorpayRewind-${user?.current}`) !== 'false') {
      togglePaymentsRecapModal(true);
    }
  }, []);

  if (isLoading || isError || !slides.length) {
    return null;
  }

  return (
    <Modal
      isOpen={isOpen}
      onClose={() => {
        togglePaymentsRecapModal(false);
        setItem(`showRazorpayRewind-${user?.current}`, 'false');
        setCapturedSlides(new Map());
      }}
      closeable={true}
      isDenserBackdrop={true}
    >
      <PaymentsRecapContainer>
        <CarouselContainer ref={componentRef}>
          <Stories
            stories={stories as any}
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
                <Text color="interactive.text.staticWhite.normal" size="large" weight="semibold">
                  Share to
                </Text>
                <IconButton
                  icon={() => <CloseIcon color="interactive.icon.primary.disabled" />}
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
  );
};

const mapStateToProps = (state) => ({
  paymentsRecap: state.paymentsRecap,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      togglePaymentsRecapModal: togglePaymentsRecapModalFn,
      togglePaymentsRecapBannerVisibility: togglePaymentsRecapBannerVisibilityFn,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PaymentsRecap);
