import React, { useState, useMemo } from 'react';
import {
  Box,
  Button,
  Heading,
  Text,
  CheckCircle2Icon,
  ArrowRightIcon,
} from '@razorpay/blade/components';

import { trackStartRegistrationCtaClick, trackEventOnUserScreenCtaClick } from '../analytics';
import { HEADER_BENEFITS_OFFER, RIZE_INCORPORATION, RIZE_JOURNEY } from '../constant';
import { parseBannerData, styleBasedOnDevice } from '../utils';

import type {
  HeaderSectionT,
  IconWithTextUiT,
  IconWithTextWrapperT,
  RizeJourneyType,
} from '../types';

import { ConfirmationPopUp } from './ConfirmationPopUp';

export const commonColor = 'surface.text.staticWhite.normal' as const;

export const IconWithTextUI = ({
  Icon,
  iconColor,
  textColor,
  LoopOverData,
}: IconWithTextUiT): JSX.Element => {
  return (
    <>
      {LoopOverData.map((text) => (
        <Box key={text} display="flex" justifyContent="flex-start" alignItems="center">
          <Icon color={iconColor} />
          <Text size="large" marginLeft="spacing.3" color={textColor}>
            {text}
          </Text>
        </Box>
      ))}
    </>
  );
};

const IconWithTextWrapper = ({
  Icon,
  iconColor,
  textColor,
  LoopOverData,
  isSmallDevice,
}: IconWithTextWrapperT) => {
  return (
    <Box
      width="100%"
      display="grid"
      gridTemplateColumns={`repeat(auto-fit, minmax(${
        isSmallDevice ? '100%' : '160px'
      }, max-content))`}
      justifyContent="flex-start"
      columnGap="spacing.5"
      rowGap={isSmallDevice ? 'spacing.5' : 'spacing.0'}
      marginTop={isSmallDevice ? 'spacing.7' : 'spacing.5'}
      marginBottom={isSmallDevice ? 'spacing.7' : 'spacing.8'}
      testID="icon-wrapper"
    >
      <IconWithTextUI
        Icon={Icon}
        iconColor={iconColor}
        textColor={textColor}
        LoopOverData={LoopOverData}
      />
    </Box>
  );
};

export const HeaderSection = ({ screen, bannerData, isSmallDevice }: HeaderSectionT) => {
  return (
    <Box>
      <Box display="flex">
        <Heading
          size={isSmallDevice ? 'xlarge' : 'large'}
          color={commonColor}
          weight={
            screen != RIZE_JOURNEY.ACCOUNT_SCREEN
              ? 'semibold'
              : isSmallDevice
              ? 'semibold'
              : 'regular'
          }
        >
          {bannerData.firstLine}{' '}
          {screen != RIZE_JOURNEY.ACCOUNT_SCREEN ? bannerData.secondLineSubText : null}
        </Heading>
      </Box>
      <Box display="flex">
        {screen == RIZE_JOURNEY.ACCOUNT_SCREEN ? (
          <Heading
            size={isSmallDevice ? 'xlarge' : 'large'}
            marginRight="spacing.3"
            color={commonColor}
            weight="semibold"
          >
            {bannerData.secondLineSubText}
          </Heading>
        ) : null}
        <Heading color={commonColor} size={isSmallDevice ? 'xlarge' : 'large'} weight="semibold">
          {bannerData.highlightedText}
        </Heading>
      </Box>
    </Box>
  );
};
const CompanyRegisterBanner = ({
  isSmallDevice,
  screen,
}: {
  isSmallDevice: boolean;
  screen: RizeJourneyType;
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const bannerData = parseBannerData({ screen });

  const styles = useMemo(() => styleBasedOnDevice(isSmallDevice), [isSmallDevice]);

  const closeModal = () => {
    setIsOpen(false);
  };
  const handleUserAction = () => {
    window.open(RIZE_INCORPORATION, '_blank', 'noopener');
    trackStartRegistrationCtaClick();
    closeModal();
  };
  const handleClickAction = () => {
    if (screen == RIZE_JOURNEY.INITIAL_SCREEN) {
      setIsOpen(true);
    } else {
      trackEventOnUserScreenCtaClick();
      window.open(RIZE_INCORPORATION, '_blank', 'noopener');
    }
  };
  const confirmationProps = {
    isOpen,
    closeModal,
    handleUserAction,
    isSmallDevice,
  };

  return (
    <Box {...(styles as any)}>
      <HeaderSection screen={screen} bannerData={bannerData} isSmallDevice={isSmallDevice} />
      {screen == RIZE_JOURNEY.INITIAL_SCREEN || screen == RIZE_JOURNEY.RESUME_SCREEN ? (
        <IconWithTextWrapper
          Icon={CheckCircle2Icon}
          iconColor="surface.icon.staticWhite.normal"
          textColor={commonColor}
          LoopOverData={HEADER_BENEFITS_OFFER}
          isSmallDevice={isSmallDevice}
        />
      ) : null}
      {screen == RIZE_JOURNEY.STATUS_SCREEN ? (
        <Text size="large" marginTop="spacing.7" color={commonColor}>
          Sit back & relax while we take care of the entire process
        </Text>
      ) : null}
      {bannerData.isButtonRequire ? (
        <Button
          variant="primary"
          color="white"
          size="medium"
          icon={ArrowRightIcon}
          iconPosition="right"
          onClick={handleClickAction}
          isFullWidth={isSmallDevice}
        >
          {bannerData.buttonText}
        </Button>
      ) : null}
      <ConfirmationPopUp {...confirmationProps} />
    </Box>
  );
};

export default CompanyRegisterBanner;
