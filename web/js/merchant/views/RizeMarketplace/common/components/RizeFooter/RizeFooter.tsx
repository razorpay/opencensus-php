import React from 'react';
import {
  Box,
  Button,
  Carousel,
  CarouselItem,
  Text,
  Title,
  useTheme,
} from '@razorpay/blade/components';

import RizeFooterBgImage from 'assets/rize/marketplace/rize-footer-bg.jpg';
import { trackKnowMoreCommunityInitiated } from 'merchant/views/RizeMarketplace/common/analytics';
import RizeLogo from 'merchant/views/RizeMarketplace/common/components/RizeLogo';
import { WEBSITE_LINKS } from 'merchant/views/RizeMarketplace/common/constants';

import TestimonialCard from './TestimonialCard';
import { TESTIMONIALS } from './constants';
import { RizeFooterProps } from './types';

const RizeFooter = ({ source }: RizeFooterProps): JSX.Element => {
  const { theme } = useTheme();

  return (
    <Box
      display="grid"
      gridTemplateColumns={{ l: '1fr 1.75fr' }}
      alignItems="center"
      gap="5.5rem"
      paddingY="6rem"
      paddingLeft={{ base: 'spacing.7', l: '10.5rem' }}
      paddingRight={{ base: 'spacing.7', l: 'spacing.0' }}
      backgroundImage={`url(${RizeFooterBgImage})`}
      backgroundSize="cover"
      testID="rize-footer"
    >
      <Box flexShrink={0}>
        <RizeLogo isDuotone color={theme.colors.static.white} />
        <Title size="xlarge" marginTop="spacing.4" color="surface.text.normal.highContrast">
          Join a community of 600+ Founders creating Success Stories.
        </Title>
        <Text size="large" marginTop="spacing.3" color="surface.text.muted.highContrast">
          We’re more than a Startup Program; We’re your Partners in Growth! Ready to “Rize” with us?
        </Text>
        <Button
          href={WEBSITE_LINKS.RIZE_HOMEPAGE}
          target="_blank"
          size="large"
          marginTop="spacing.8"
          onClick={(): void => trackKnowMoreCommunityInitiated(source)}
        >
          Know More
        </Button>
      </Box>

      <Box overflow="hidden" display={{ base: 'none', l: 'block' }}>
        <Carousel visibleItems={2} autoPlay>
          {TESTIMONIALS.map((testimonial, idx) => (
            <CarouselItem key={idx}>
              <TestimonialCard {...testimonial} />
            </CarouselItem>
          ))}
        </Carousel>
      </Box>
    </Box>
  );
};

export default RizeFooter;
