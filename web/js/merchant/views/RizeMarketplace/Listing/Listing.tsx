import React, { useEffect } from 'react';
import { BladeProvider, Box, Heading, Spinner, Text } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import {
  trackDealTileClicked,
  trackProductLoadedSuccess,
} from 'merchant/views/RizeMarketplace/common/analytics';
import BackgroundIntroVideo from 'merchant/views/RizeMarketplace/common/components/BackgroundIntroVideo';
import Container from 'merchant/views/RizeMarketplace/common/components/Container';
import {
  ProductCardTile,
  ProductCardTileSkeleton,
} from 'merchant/views/RizeMarketplace/common/components/ProductCardTile';
import RizeFooter from 'merchant/views/RizeMarketplace/common/components/RizeFooter';
import { useRizeMarketplaceStore } from 'merchant/views/RizeMarketplace/common/store';
import { APIError, Result } from 'merchant/views/RizeMarketplace/common/types';
import {
  resolveToRizeUrl,
  useMarketplaceProduct,
  useMarketplaceSimilarProducts,
  withRizeMarketplaceExperimentGuard,
} from 'merchant/views/RizeMarketplace/common/utils';

import BackToMarketplaceLink from './components/BackToMarketplaceLink';
import ListingDetails from './components/ListingDetails';
import { ProductLogo, StyledYoutubeVideo } from './styled';
import { ListingPageProps } from './types';

const ListingPage = ({ slug }: ListingPageProps): JSX.Element => {
  const {
    data: product,
    isLoading: isProductLoading,
    isError: isProductError,
    isSuccess: isProductSuccess,
  } = useMarketplaceProduct(slug, {
    // No point in retrying if product was not found
    retry: (count, error?: APIError) => count < 3 && error?.downstream_status_code !== 404,
  });
  const { data: similarProducts, isLoading: isSimilarProductsLoading } =
    useMarketplaceSimilarProducts(product?.id, product?.data?.category);

  const setIsDealAvailed = useRizeMarketplaceStore((state) => state.setIsDealAvailed);

  // Set deal availed to false when the page unmounts or slug changes
  useEffect(
    () => () => {
      setIsDealAvailed(false);
    },
    [slug, setIsDealAvailed],
  );

  useEffect(() => {
    if (!product?.data) return;

    trackProductLoadedSuccess({
      id: product.id,
      name: product.data.name,
      category: product.data.category,
      type: product.data.type,
    });
  }, [isProductSuccess, product]);

  const handleSimilarProductClick = (similarProduct: Result): void => {
    trackDealTileClicked(
      {
        name: similarProduct.name,
        category: similarProduct.category,
        id: similarProduct.id,
        type: similarProduct.type,
      },
      'More_in_Category',
    );
  };

  if (!product?.data) {
    return (
      <Box
        height="100vh"
        display="flex"
        flexDirection="column"
        alignItems="center"
        justifyContent="center"
      >
        {isProductLoading ? (
          <Spinner size="xlarge" accessibilityLabel="Loading product details" />
        ) : null}
        {isProductError ? (
          <>
            <Text size="large" weight="semibold" color="surface.text.gray.subtle">
              Could not load product details, please try again later.
            </Text>
            <BackToMarketplaceLink marginTop="spacing.5" />
          </>
        ) : null}
      </Box>
    );
  }

  return (
    <Box backgroundColor="surface.background.gray.moderate">
      <BladeProvider colorScheme="dark" themeTokens={bladeTheme}>
        <Box paddingTop="spacing.9" paddingBottom="spacing.11" position="relative" zIndex={1}>
          <BackgroundIntroVideo />
          <Container>
            <BackToMarketplaceLink color="white" alignSelf="flex-start" />
            <Box
              display="flex"
              flexDirection={{ base: 'column', l: 'row' }}
              justifyContent="space-between"
              alignItems="center"
              gap={{ base: '36px', l: 'spacing.0' }}
              marginTop={{ base: '5.5rem', l: 'spacing.7' }}
            >
              <Box
                display="flex"
                flexDirection="column"
                gap="spacing.5"
                maxWidth="60%"
                width="100%"
              >
                <Box
                  display="flex"
                  flexDirection={{ base: 'column', l: 'row' }}
                  alignItems="center"
                  gap="spacing.5"
                >
                  <ProductLogo
                    src={resolveToRizeUrl(product.data.logo_src)}
                    alt={`${product.data.name} logo`}
                  />
                  <Heading as="h1" color="surface.text.staticWhite.normal" size="xlarge">
                    {product.data.name}
                  </Heading>
                </Box>
                <Text size="small" weight="semibold" color="surface.text.staticWhite.normal">
                  {product.data.category.toUpperCase()}
                </Text>
                <Box maxWidth="75%" display={{ base: 'none', l: 'block' }}>
                  <Heading
                    weight="regular"
                    as="span"
                    color="surface.text.staticWhite.normal"
                    size="medium"
                  >
                    {product.data.excerpt}
                  </Heading>
                </Box>
              </Box>
              <StyledYoutubeVideo src={product.data.video_src} title={product.data.name} />
            </Box>
          </Container>
        </Box>
      </BladeProvider>
      <ListingDetails product={product} />
      <Box
        backgroundColor="surface.background.gray.subtle"
        paddingTop={{ base: '28px', l: 'spacing.11' }}
        paddingBottom="74px"
      >
        <Container>
          <Heading as="h2" color="surface.text.gray.normal" size="large">
            More in {product.data.category}
          </Heading>

          {!isSimilarProductsLoading &&
          (!similarProducts || similarProducts.results.length === 0) ? (
            <Box display="flex" justifyContent="center" marginY="spacing.11">
              <Text
                color="surface.text.gray.subtle"
                size="large"
                weight="semibold"
                textAlign="center"
              >
                {!similarProducts
                  ? 'Could not load similar products, check again later.'
                  : 'No similar products found'}
              </Text>
            </Box>
          ) : (
            <Box
              marginTop={{ base: 'spacing.8', l: 'spacing.7' }}
              display="grid"
              gridTemplateColumns={{ base: '1fr', l: '1fr 1fr' }}
              gap={{ base: 'spacing.7', l: 'spacing.8' }}
            >
              {isSimilarProductsLoading
                ? new Array(4).fill(null).map((_, i) => <ProductCardTileSkeleton key={i} />)
                : similarProducts?.results.map(
                    (similarProduct): JSX.Element => (
                      <ProductCardTile
                        key={similarProduct.slug}
                        name={similarProduct.name}
                        slug={similarProduct.slug}
                        logoSrc={similarProduct.logo_src}
                        category={similarProduct.category}
                        excerpt={similarProduct.excerpt}
                        offer={similarProduct.offer}
                        onClick={(): void => handleSimilarProductClick(similarProduct)}
                      />
                    ),
                  )}
            </Box>
          )}
        </Container>
      </Box>
      <RizeFooter source="productpage" />
    </Box>
  );
};

export default withRizeMarketplaceExperimentGuard(ListingPage);
