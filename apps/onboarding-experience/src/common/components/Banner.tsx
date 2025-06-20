import React, { ReactElement } from 'react';
import { Box, Heading, Text, BoxProps } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';

export type BannerPropsType = {
  iconSrc: string;
  bgImage: BoxProps['backgroundImage'];
  bgImageMobile: BoxProps['backgroundImage'];
  title: string;
  description: string;
  subDescription?: string;
  CTA?: () => ReactElement;
};

const Banner = ({
  iconSrc,
  bgImage,
  bgImageMobile,
  title,
  description,
  subDescription,
  CTA,
}: BannerPropsType) => {
  const isMobile = isMobileDevice();
  return (
    <Box
      display="flex"
      width="100%"
      paddingY={{
        base: 'spacing.9',
        m: 'spacing.10',
      }}
      flexDirection="column"
      justifyContent="center"
      alignItems="center"
      gap={{
        base: 'spacing.5',
        m: 'spacing.7',
      }}
      borderRadius="large"
      backgroundImage={`url(${isMobile ? bgImageMobile : bgImage})`}
      backgroundRepeat="round"
    >
      <Box
        display="flex"
        paddingX={{
          base: 'spacing.5',
          m: 'spacing.7',
        }}
        flexDirection="column"
        justifyContent="center"
        alignItems="center"
        gap="spacing.5"
      >
        <Box
          width="48px"
          height="48px"
          borderRadius="large"
          backgroundColor="overlay.background.moderate"
          backgroundImage={`url(${iconSrc})`}
        />
        <Box display="flex" flexDirection="column" alignItems="center" gap="spacing.3">
          <Heading size={isMobile ? 'large' : 'medium'} textAlign="center" weight="semibold">
            {title}
          </Heading>
          <Box display="flex" flexDirection="column" gap="spacing.5">
            <Text size="small" textAlign="center" color="surface.text.gray.muted">
              {description}
            </Text>
            {subDescription && (
              <Text size="small" textAlign="center" color="surface.text.gray.muted">
                {subDescription}
              </Text>
            )}
          </Box>
        </Box>
      </Box>
      {CTA && <CTA />}
    </Box>
  );
};

export default Banner;
