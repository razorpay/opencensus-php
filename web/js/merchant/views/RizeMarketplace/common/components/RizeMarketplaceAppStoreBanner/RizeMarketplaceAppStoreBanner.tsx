import React, { useEffect } from 'react';
import {
  Box,
  Button,
  Card,
  CardBody,
  Carousel,
  CarouselItem,
  ExternalLinkIcon,
  Link,
  Text,
  Title,
  useTheme,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';

import {
  trackCarouselLoadedSuccess,
  trackDealTileClicked,
  trackVisitMarketplaceInitiated,
} from 'merchant/views/RizeMarketplace/common/analytics';
import {
  ProductCardTile,
  ProductCardTileSkeleton,
} from 'merchant/views/RizeMarketplace/common/components/ProductCardTile';
import RizeTag from 'merchant/views/RizeMarketplace/common/components/RizeTag';
import { Result } from 'merchant/views/RizeMarketplace/common/types';
import { useMarketplaceWhatsNew } from 'merchant/views/RizeMarketplace/common/utils';

const RizeMarketplaceAppStoreBanner = (): JSX.Element => {
  const { isLoading, isError, data } = useMarketplaceWhatsNew();
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint(theme);
  const isL = matchedBreakpoint === 'l' || matchedBreakpoint === 'xl';

  useEffect(() => {
    trackCarouselLoadedSuccess();
  }, []);

  const handleProductClick = (product: Result): void => {
    trackDealTileClicked(
      {
        name: product.name,
        category: product.category,
        id: product.id,
        type: product.type,
      },
      'app_store_carousel',
    );
  };

  return (
    <Card surfaceLevel={2} padding="spacing.0">
      <CardBody>
        <Box
          display="grid"
          alignItems="center"
          gridTemplateColumns={{ base: '1fr', l: '1fr 2.5fr' }}
          gap={{ base: 'spacing.5', l: 'spacing.6' }}
          maxWidth="100%"
          paddingTop={{ base: 'spacing.9', l: isError ? 'spacing.10' : 'spacing.6' }}
          paddingBottom={{ base: 'spacing.6', l: isError ? 'spacing.10' : 'spacing.6' }}
          paddingLeft={{ base: 'spacing.6', l: 'spacing.10' }}
          paddingRight={{ base: 'spacing.6', l: 'spacing.0' }}
          backgroundImage="linear-gradient(193deg, #1566F1 -194.8%, #2D2257 143.88%)"
          borderRadius="medium"
          position="relative"
        >
          <Box marginTop={{ l: 'spacing.6' }}>
            <Box
              position={{ l: 'absolute' }}
              top="spacing.7"
              left="-8px"
              display="inline-block"
              marginBottom="spacing.4"
            >
              <RizeTag showFlap={isL} />
            </Box>
            <Title as="h2" size="small" color="surface.text.normal.highContrast">
              {isL
                ? 'Unlock Marketplace for Exclusive Deals'
                : 'Discover 40 + exciting deals on Rize marketplace'}
            </Title>
            <Text
              size="large"
              color="surface.text.normal.highContrast"
              marginTop="spacing.4"
              display={{ base: 'none', l: 'block' }}
            >
              Access 40+ Product deals exclusively built by Startup Founders.
            </Text>

            {isL ? (
              <Button
                href="/app/rize-marketplace"
                target="_blank"
                icon={ExternalLinkIcon}
                iconPosition="right"
                size="large"
                marginTop="spacing.7"
                onClick={(): void => {
                  trackVisitMarketplaceInitiated();
                }}
              >
                Visit Marketplace
              </Button>
            ) : (
              <Link
                href="/app/rize-marketplace"
                target="_blank"
                icon={ExternalLinkIcon}
                iconPosition="right"
                size="medium"
                color="white"
                marginTop="spacing.4"
                onClick={(): void => {
                  trackVisitMarketplaceInitiated();
                }}
              >
                Visit Marketplace
              </Link>
            )}
          </Box>

          <Box overflow="hidden">
            <Carousel
              navigationButtonPosition={isL ? 'side' : 'bottom'}
              showIndicators={!isL}
              visibleItems={isL ? 'autofit' : 1}
              carouselItemWidth={{ base: '100%', l: '550px' }}
              carouselItemAlignment="stretch"
              autoPlay
            >
              {!data
                ? new Array(4).fill(null).map((_, i) => (
                    <CarouselItem key={i}>
                      {isLoading ? (
                        <Box
                          paddingY={{ l: 'spacing.10' }}
                          height="100%"
                          marginLeft={{ l: i === 0 ? 'spacing.6' : 'spacing.0' }}
                          marginRight={{
                            l: i === 3 ? 'spacing.6' : 'spacing.0',
                          }}
                        >
                          <ProductCardTileSkeleton />
                        </Box>
                      ) : null}
                    </CarouselItem>
                  ))
                : data.results.map((product, i) => (
                    <CarouselItem key={product.slug}>
                      <Box
                        paddingY={{ l: 'spacing.9' }}
                        height="100%"
                        marginLeft={{ l: i === 0 ? 'spacing.6' : 'spacing.2' }}
                        marginRight={{
                          l: i === data.results.length - 1 ? 'spacing.7' : 'spacing.2',
                        }}
                      >
                        <ProductCardTile
                          name={product.name}
                          slug={product.slug}
                          logoSrc={product.logo_src}
                          category={product.category}
                          excerpt={product.excerpt}
                          offer={product.offer}
                          showKnowMoreCTA
                          truncateExcerpt={2}
                          target="_blank"
                          onClick={(): void => handleProductClick(product)}
                        />
                      </Box>
                    </CarouselItem>
                  ))}
            </Carousel>
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};

export default RizeMarketplaceAppStoreBanner;
