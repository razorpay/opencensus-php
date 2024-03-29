import React, { useEffect, useRef, useState } from 'react';
import {
  BottomSheet,
  BottomSheetBody,
  BottomSheetHeader,
  Box,
  Button,
  Card,
  CardBody,
  CopyIcon,
  Heading,
  Link,
  Text,
} from '@razorpay/blade/components';
import { useQueryClient } from '@tanstack/react-query';

import useClipboard from 'merchant/hooks/useClipboard';
import {
  trackApplyHereCtaClicked,
  trackAvailThisDealCtaClicked,
  trackCopyCouponCodeClicked,
  trackVisitWebsiteClicked,
} from 'merchant/views/RizeMarketplace/common/analytics';
import { useRizeMarketplaceStore } from 'merchant/views/RizeMarketplace/common/store';
import { ProductResponse } from 'merchant/views/RizeMarketplace/common/types';
import { getMarketplaceProductQueryKey } from 'merchant/views/RizeMarketplace/common/utils';

import AvailedBackground from './AvailedBackground';
import { DashedBox } from './styled';
import { DealCardProps, SmallDealCardProps } from './types';

const useTrackingProductData = (
  slug: string,
): Parameters<typeof trackAvailThisDealCtaClicked>[0] | undefined => {
  const queryClient = useQueryClient();
  const productData = queryClient.getQueryData<ProductResponse>(
    getMarketplaceProductQueryKey(slug),
  );

  if (!productData?.data) {
    console.error(
      'Product data not found in Query Cache. Render this component in a parent company which calls `useMarketplaceProduct` hook',
    );
    return undefined;
  }

  return {
    id: productData.id,
    name: productData.data.name ?? '',
    category: productData.data.category ?? '',
    type: productData.data.type,
  };
};

const LargeDealCard = ({
  slug,
  offer,
  couponCode = '',
  availLink,
  defaultIsAvailed = false,
}: DealCardProps): JSX.Element | null => {
  const isAvailed = useRizeMarketplaceStore((state) => state.isDealAvailed);
  const setAvailed = useRizeMarketplaceStore((state) => state.setIsDealAvailed);

  const { copy, isCopied } = useClipboard(3000);
  const unaviledCardRef = useRef<HTMLDivElement>(null);
  const [minHeight, setMinHeight] = useState(0);
  const trackingProductData = useTrackingProductData(slug);

  useEffect(() => {
    if (!unaviledCardRef.current) return;

    setMinHeight(unaviledCardRef.current.offsetHeight);
  }, []);

  if (!trackingProductData) return null;

  const Unavailed = (
    <Box
      display="flex"
      flexDirection="column"
      alignItems="center"
      justifyContent="center"
      gap="spacing.6"
      padding="spacing.7"
      width="100%"
      ref={unaviledCardRef}
    >
      <Heading textAlign="center" size="large">
        {offer}
      </Heading>
      <Button
        size="large"
        isFullWidth
        onClick={(): void => {
          setAvailed(true);
          trackAvailThisDealCtaClicked(trackingProductData);
        }}
      >
        Avail Deal
      </Button>
    </Box>
  );

  const AvailedCoupon = (
    <Box
      display="flex"
      flexDirection="column"
      alignItems="center"
      justifyContent="center"
      width="100%"
    >
      <AvailedBackground />
      <Box
        backgroundColor="surface.background.primary.intense"
        paddingY="spacing.4"
        width="100%"
        borderTopLeftRadius="medium"
        borderTopRightRadius="medium"
        position="relative"
        zIndex={1}
      >
        <Text color="surface.text.staticWhite.normal" textAlign="center" size="large">
          {offer}
        </Text>
      </Box>
      <Box marginY="auto" paddingX="spacing.11" paddingY="spacing.5" width="100%">
        <Box
          display="flex"
          flexDirection="column"
          alignItems="center"
          justifyContent="center"
          gap="spacing.4"
          position="relative"
          borderRadius="medium"
          overflow="hidden"
          backgroundColor="surface.background.gray.intense"
          maxWidth="100%"
        >
          <Box
            display="flex"
            flexDirection="column"
            alignItems="center"
            paddingTop="spacing.6"
            paddingX="spacing.6"
            gap="spacing.3"
          >
            <Box maxWidth="100%" whiteSpace="break-spaces">
              <Text
                weight="semibold"
                size="large"
                textAlign="center"
                testID="large-deal-card-coupon-code"
              >
                {couponCode}
              </Text>
            </Box>
            {isCopied ? (
              <Text
                size="small"
                weight="semibold"
                color="surface.text.gray.subtle"
                textAlign="center"
              >
                Code copied!
              </Text>
            ) : (
              <Link
                variant="button"
                size="small"
                icon={CopyIcon}
                iconPosition="left"
                onClick={(): void => {
                  copy(couponCode);
                  trackCopyCouponCodeClicked(trackingProductData);
                }}
              >
                Copy Code
              </Link>
            )}
          </Box>
          <Button
            href={availLink}
            target="_blank"
            size="large"
            isFullWidth
            onClick={(): void => trackApplyHereCtaClicked(trackingProductData)}
          >
            Apply here
          </Button>

          <DashedBox />
        </Box>
      </Box>
    </Box>
  );

  const AvailedLink = (
    <Box display="flex" flexDirection="column" alignItems="center" width="100%">
      <AvailedBackground />
      <Box
        backgroundColor="surface.background.primary.intense"
        paddingY="spacing.4"
        width="100%"
        borderTopLeftRadius="medium"
        borderTopRightRadius="medium"
        position="relative"
        zIndex={1}
      >
        <Text color="surface.text.staticWhite.normal" textAlign="center" size="large">
          {offer}
        </Text>
      </Box>
      <Box
        display="flex"
        flexDirection="column"
        alignItems="center"
        position="relative"
        paddingX="4rem"
        paddingY="spacing.8"
        marginY="auto"
      >
        <Button
          href={availLink}
          target="_blank"
          size="large"
          isFullWidth
          onClick={(): void => trackVisitWebsiteClicked(trackingProductData)}
        >
          Apply here
        </Button>
        <Box marginTop="spacing.3" paddingX="spacing.8">
          <Text color="surface.text.gray.subtle" textAlign="center">
            You can avail the deal on product’s website
          </Text>
        </Box>
      </Box>
    </Box>
  );

  const Availed = couponCode ? AvailedCoupon : AvailedLink;

  return (
    <Card padding="spacing.0">
      <CardBody>
        <Box display="flex" minHeight={`${minHeight}px`} position="relative" overflow="hidden">
          {isAvailed || defaultIsAvailed ? Availed : Unavailed}
        </Box>
      </CardBody>
    </Card>
  );
};

const SmallDealCard = ({
  slug,
  offer,
  couponCode = '',
  availLink,
  defaultIsAvailed = false,
  isSticky = false,
}: SmallDealCardProps): JSX.Element | null => {
  const [isOpen, setIsOpen] = useState(defaultIsAvailed);
  const trackingProductData = useTrackingProductData(slug);

  if (!trackingProductData) return null;

  return (
    <>
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        gap="spacing.5"
        padding={['spacing.6', isSticky ? 'spacing.8' : 'spacing.4']}
        backgroundColor="surface.background.gray.moderate"
        borderRadius={isSticky ? 'none' : 'medium'}
        borderTopWidth={isSticky ? 'thin' : 'none'}
        borderTopColor={isSticky ? 'surface.border.gray.muted' : undefined}
      >
        <Heading weight="semibold" size="small">
          {offer}
        </Heading>
        <Box flexShrink={0}>
          <Button
            size="large"
            onClick={(): void => {
              setIsOpen(true);
              trackAvailThisDealCtaClicked(trackingProductData);
            }}
          >
            Avail Deal
          </Button>
        </Box>
      </Box>
      <BottomSheet
        /**
         * This high zIndex is to overlay the mobile filters section on top of the "Help" button.
         * "Help" button in context of Rize marketplace is irrelevant as the support does not provide help with the marketplace.
         * Check with design for more clarifications.
         */
        zIndex={99999}
        isOpen={isOpen}
        snapPoints={[1.0, 1.0, 1.0]}
        onDismiss={(): void => setIsOpen(false)}
      >
        <BottomSheetHeader title="Avail Deal" />
        <BottomSheetBody>
          <LargeDealCard
            slug={slug}
            availLink={availLink}
            offer={offer}
            couponCode={couponCode}
            defaultIsAvailed
          />
        </BottomSheetBody>
      </BottomSheet>
    </>
  );
};

export { LargeDealCard, SmallDealCard };
