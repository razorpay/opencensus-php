import React, { useRef, useState } from 'react';
import {
  Box,
  Button,
  IconButton,
  BottomSheet,
  BottomSheetBody,
  Text,
  CloseIcon,
  ShareIcon,
  Carousel,
  CarouselItem,
  Modal,
  ModalHeader,
  ModalBody,
} from '@razorpay/blade/components';

import { CarouselContainer, PaymentsRecapContainer, SocialShareBottomSheet } from './styled';
import { socialShare, trackPaymentsRecapEvent } from './utils';

import Image from 'common/ui/Image';
import CustomShareComponent from './ShareComponent';
import { captureImage } from './imageUtils';

const RewindModal = ({ isOpen, onDismiss, slides, isMobile, isNativeWebShare, isTablet }) => {
  const [isBottomSheetOpen, setIsBottomSheetOpen] = useState(false);
  const imgDimension = isMobile ? 300 : 400;
  const currentStoryKey = useRef('');
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
      captureImage(componentRef, isMobile || isTablet, action);
    } else {
      socialShare({
        componentRef: componentRef,
        isMobile: isMobile,
        isTablet: isTablet,
        type: action,
      });
    }
  };

  const handleShare = () => {
    if (!isMobile || !isNativeWebShare) {
      setIsBottomSheetOpen(true);
    } else {
      handleShareAction('navigator-share');
    }
  };

  const onCarouselSlideChange = (slideIndex: number) => {
    currentStoryKey.current = slides[slideIndex].key;
    trackPaymentsRecapEvent({
      objectName: 'RZP Rewind Card',
      actionName: 'Viewed',
      properties: {
        current: slides[slideIndex].key,
        total: slides.length,
      },
    });
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} size="medium">
      <ModalHeader />
      <ModalBody padding="spacing.0">
        <PaymentsRecapContainer>
          <CarouselContainer ref={componentRef}>
            <Carousel
              indicatorVariant="white"
              navigationButtonPosition="side"
              marginTop={'spacing.3'}
              autoPlay={!isBottomSheetOpen}
              onChange={onCarouselSlideChange}
            >
              {slides.map(({ imgOverlay, imgSrc, key }) => {
                return (
                  <CarouselItem key={key}>
                    <Box position="relative">
                      {imgOverlay}
                      <Image
                        src={imgSrc}
                        style={{
                          maxWidth: isMobile ? '85%' : '100%',
                          marginLeft: isMobile ? '6vw' : '0px',
                        }}
                      />
                    </Box>
                  </CarouselItem>
                );
              })}
            </Carousel>
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
            >
              <BottomSheetBody padding="spacing.5">
                <Box
                  display="flex"
                  flexDirection="row"
                  alignItems="center"
                  justifyContent="space-between"
                  paddingX="spacing.5"
                >
                  <Box flex="1" />
                  <Text
                    color="interactive.text.staticWhite.normal"
                    size="large"
                    weight="semibold"
                    textAlign="center"
                  >
                    Share this via
                  </Text>
                  <Box flex="1" display="flex" justifyContent="flex-end">
                    <IconButton
                      icon={() => <CloseIcon color="feedback.icon.neutral.subtle" />}
                      onClick={() => setIsBottomSheetOpen(false)}
                      accessibilityLabel="share-drawer-close"
                    />
                  </Box>
                </Box>
                <CustomShareComponent handleShareAction={handleShareAction} />
              </BottomSheetBody>
            </BottomSheet>
          </SocialShareBottomSheet>
        </PaymentsRecapContainer>
      </ModalBody>
    </Modal>
  );
};

export default RewindModal;
