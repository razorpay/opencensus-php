import React, { useMemo } from 'react';
import {
  Box,
  Button,
  Heading,
  Text,
  CheckCircle2Icon,
  ArrowRightIcon,
} from '@razorpay/blade/components';

import { trackStartRegistrationCtaClick } from '../analytics';
import { HEADER_BENEFITS_OFFER, RIZE_INCORPORATION } from '../constant';
import { parseBannerData, styleBasedOnDevice } from '../utils';

import type { HeaderSectionT, IconWithTextUiT, IconWithTextWrapperT } from '../types';

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

export const HeaderSection = ({ bannerData, isSmallDevice }: HeaderSectionT) => {
  return (
    <Box>
      <Heading size={isSmallDevice ? 'xlarge' : 'large'} color={commonColor} weight="semibold">
        {bannerData.firstLine} {isSmallDevice ? 'their' : null}
      </Heading>
      <Box display="flex">
        {!isSmallDevice ? (
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
const CompanyRegisterBanner = ({ isSmallDevice }: { isSmallDevice: boolean }) => {
  const bannerData = parseBannerData({ screenNumber: 0 }); // TODO: Update the parameter pass in Phase 2

  const styles = useMemo(() => styleBasedOnDevice(isSmallDevice), [isSmallDevice]);

  const handleClickAction = () => {
    trackStartRegistrationCtaClick();
    window.open(RIZE_INCORPORATION, '_blank', 'noopener');
  };

  return (
    <Box {...(styles as any)}>
      <HeaderSection bannerData={bannerData} isSmallDevice={isSmallDevice} />
      <IconWithTextWrapper
        Icon={CheckCircle2Icon}
        iconColor="surface.icon.staticWhite.normal"
        textColor={commonColor}
        LoopOverData={HEADER_BENEFITS_OFFER}
        isSmallDevice={isSmallDevice}
      />
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
    </Box>
  );
};

export default CompanyRegisterBanner;
