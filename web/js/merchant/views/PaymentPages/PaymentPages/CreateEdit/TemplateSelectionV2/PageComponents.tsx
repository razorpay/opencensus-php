import React from 'react';
import {
  Box,
  Text,
  Carousel,
  CarouselItem,
  Button,
  ArrowRightIcon,
  Heading
} from '@razorpay/blade/components';
import { FeatureListProps, ImageCarouselProps, PageCardProps } from './types';
import { PAYMENT_PAGES_TYPES } from './PageConfig';

export const FeatureList: React.FC<FeatureListProps> = ({ features, isMobile }) => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      gap={isMobile ? 'spacing.4' : 'spacing.5'}
      marginBottom={isMobile ? "spacing.7" :"spacing.9"}
    >
      {features.map((feature, index) => (
        <Box key={index} display="flex" alignItems="center" gap="spacing.3">
          {feature.icon}
          <Text variant='body' size={isMobile ? 'small' : 'large'} weight={isMobile ? "regular" : "medium"}>
            {isMobile ? feature.textMobile : feature.textDesktop}
          </Text>
        </Box>
      ))}
    </Box>
  );
};

export const ImageCarousel: React.FC<ImageCarouselProps> = ({ images }) => {
  return (
    <Carousel>
      {images.map((image, index) => (
        <CarouselItem key={index}>
          <img src={image.src as unknown as string} width="100%" alt={image.alt} />
        </CarouselItem>
      ))}
    </Carousel>
  );
};

export const PageCard: React.FC<PageCardProps> = ({ config, onCreateClick, type, isMobile }) => {
  return (
    <Box
      backgroundColor={
        type === PAYMENT_PAGES_TYPES.payment_page
          ? 'surface.background.sea.subtle'
          : 'surface.background.primary.subtle'
      }
      padding={isMobile ? 'spacing.4' : 'spacing.7'}
      borderRadius="large"
    >
      <Box
        display="flex"
        justifyContent="space-between"
        alignItems="center"
        marginBottom="spacing.7"
      >
        <Box display="flex" flexDirection="column" gap="spacing.2">
          <Heading size={isMobile ? "small" :"medium"} weight="semibold" color="surface.text.gray.normal">
            {config.title}
          </Heading>
          <Text
            size={isMobile ? 'small' : 'large'}
            weight="semibold"
            variant="body"
            color="surface.text.gray.normal"
          >
            {config.subtitle}
          </Text>
        </Box>
        {!isMobile ? (
          <Button variant="primary" onClick={onCreateClick} icon={ArrowRightIcon}>
            {type === PAYMENT_PAGES_TYPES.payment_page
              ? 'Create Payment Page'
              : 'Create Storefront'}
          </Button>
        ) : null}
      </Box>

      <FeatureList features={config.features} isMobile={isMobile} />

      {isMobile ? (
        <Button
          variant="primary"
          onClick={onCreateClick}
          icon={ArrowRightIcon}
          marginBottom="spacing.7"
        >
          {type === PAYMENT_PAGES_TYPES.payment_page ? 'Create Payment Page' : 'Create Storefront'}
        </Button>
      ) : null}

      <ImageCarousel images={config.carouselImages} />
    </Box>
  );
};
