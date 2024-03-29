import React, { useEffect, useRef } from 'react';
import {
  ArrowLeftIcon,
  BladeProvider,
  Box,
  Button,
  Display,
  Heading,
  Link,
  Spinner,
  Text,
  Theme,
  useTheme,
} from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { useBreakpoint } from '@razorpay/blade/utils';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import styled from 'styled-components';

import {
  trackCombinationFilterClicked,
  trackDealTileClicked,
  trackFilterSearchInitiated,
  trackMarketplaceLoadedSuccess,
} from 'merchant/views/RizeMarketplace/common/analytics';
import AnimatedBottomMobileBanner from 'merchant/views/RizeMarketplace/common/components/AnimatedBottomMobileBanner';
import BackgroundIntroVideo from 'merchant/views/RizeMarketplace/common/components/BackgroundIntroVideo';
import Container from 'merchant/views/RizeMarketplace/common/components/Container';
import ProductCardList from 'merchant/views/RizeMarketplace/common/components/ProductCardList';
import {
  ProductCardTile,
  ProductCardTileSkeleton,
} from 'merchant/views/RizeMarketplace/common/components/ProductCardTile';
import RizeFooter from 'merchant/views/RizeMarketplace/common/components/RizeFooter';
import RizeLogo from 'merchant/views/RizeMarketplace/common/components/RizeLogo';
import { useRizeMarketplaceStore } from 'merchant/views/RizeMarketplace/common/store';
import { Result } from 'merchant/views/RizeMarketplace/common/types';
import {
  useMarketplaceSearch,
  useMarketplaceWhatsNew,
  withRizeMarketplaceExperimentGuard,
} from 'merchant/views/RizeMarketplace/common/utils';
import { showNotification } from 'merchant_common/reducers/notifications';

import { DesktopFiltering, MobileFiltering } from './components/Filtering';
import SearchBar from './components/SearchBar';
import { MarketplaceSearchSectionProps, RizeMarketplacePageProps } from './types';

const SEARCH_HEADER_OFFSET = 200;

const MarketplaceSearchSection = ({
  showNotification,
}: MarketplaceSearchSectionProps): JSX.Element => {
  const { filters, search, setSearch, setCategory } = useRizeMarketplaceStore();
  const searchSectionRef = useRef<HTMLElement>(null);
  const userPerformedSearch = useRef<boolean>(false);

  const searchResultsTopRef = useRef<HTMLDivElement>(null);

  const {
    isFetching: isSearchLoading,
    isFetchingNextPage,
    isError,
    data: searchData,
    hasNextPage,
    fetchNextPage,
  } = useMarketplaceSearch({
    filters,
    search,
  });
  const numPages = searchData?.pages.length;

  useEffect(() => {
    if (!isError) return;

    showNotification({
      type: 'error',
      message: 'An error occurred while fetching products. Try again later.',
    });
  }, [isError, showNotification]);

  // Scroll to top of search results when user applies filters or performs a search
  useEffect(() => {
    if (
      !userPerformedSearch.current ||
      isSearchLoading ||
      numPages !== 1 ||
      !searchResultsTopRef.current
    )
      return;

    userPerformedSearch.current = false;
    const top = searchResultsTopRef.current.offsetTop - SEARCH_HEADER_OFFSET;
    window.scrollTo({
      top,
      behavior: 'smooth',
    });
  }, [isSearchLoading, numPages]);

  const handleFiltersChange = (values: string[]): void => {
    userPerformedSearch.current = true;
    setCategory(values);
    trackCombinationFilterClicked(values);
  };

  const handleProductClick = (product: Result): void => {
    trackDealTileClicked(
      {
        name: product.name,
        category: product.category,
        id: product.id,
        type: product.type,
      },
      'Products',
    );
  };

  const searchedProducts = searchData?.pages.flatMap(({ results }) => results);

  return (
    <Box ref={searchSectionRef}>
      <Box paddingBottom="6rem">
        <Box
          position="sticky"
          top="spacing.0"
          zIndex="20"
          backgroundColor="surface.background.gray.moderate"
          paddingY={{ base: 'spacing.7', l: 'spacing.8' }}
        >
          <Container>
            <Heading as="h2" color="surface.text.gray.normal" size="large">
              All Rize Deals
            </Heading>
            <Box
              display="grid"
              gridTemplateColumns={{ base: '1fr', l: '1fr 280px' }}
              gap="spacing.10"
              marginTop="spacing.7"
            >
              <SearchBar
                defaultValue={search}
                onChange={(value): void => {
                  userPerformedSearch.current = true;
                  setSearch(value);
                  trackFilterSearchInitiated(value);
                }}
              />
            </Box>
          </Container>
        </Box>

        <Container
          display="grid"
          gridTemplateColumns={{ base: '1fr', l: '1fr 280px' }}
          gap="spacing.10"
          alignItems="flex-start"
          paddingTop="spacing.8"
        >
          <Box display="flex" flexDirection="column" gap="spacing.6">
            {/* This element is a handle to the top of the search results for auto-scrolling  */}
            <Box height="1px" position="absolute" ref={searchResultsTopRef} />
            {isSearchLoading && !isFetchingNextPage ? (
              <Box paddingTop="3rem" paddingBottom="7rem" display="flex" justifyContent="center">
                <Spinner size="xlarge" accessibilityLabel="Loading products" />
              </Box>
            ) : searchedProducts?.length === 0 ? (
              <Text
                color="surface.text.gray.subtle"
                size="large"
                weight="semibold"
                textAlign="center"
                marginY="spacing.11"
              >
                No products found
              </Text>
            ) : (
              searchedProducts?.map((product) => (
                <ProductCardList
                  key={product.slug}
                  name={product.name}
                  slug={product.slug}
                  logoSrc={product.logo_src}
                  category={product.category}
                  excerpt={product.excerpt}
                  offer={product.offer}
                  onClick={(): void => handleProductClick(product)}
                />
              ))
            )}

            <Box
              display={hasNextPage ? 'flex' : 'none'}
              justifyContent="center"
              paddingTop="spacing.10"
            >
              {isFetchingNextPage ? (
                <Spinner size="xlarge" accessibilityLabel="Loading products" />
              ) : (
                <Button
                  variant="tertiary"
                  onClick={(): void => {
                    fetchNextPage();
                  }}
                >
                  Load more
                </Button>
              )}
            </Box>
          </Box>
          <DesktopFiltering value={filters.category} onChange={handleFiltersChange} />
        </Container>
      </Box>
      <AnimatedBottomMobileBanner isVisible>
        <MobileFiltering value={filters.category} onChange={handleFiltersChange} />
      </AnimatedBottomMobileBanner>
    </Box>
  );
};

const StyledRizeLogo = styled(RizeLogo)(
  ({ theme }: { theme: Theme }) => `
  height: ${theme.spacing[6]}px;
  width: auto;
  margin-top: ${theme.spacing[10]}px;
  color: ${theme.colors.interactive.icon.staticWhite.normal};

  @media (min-width: ${theme.breakpoints.m}px) {
    height: 26px;
  }
`,
);
export const StyledFadedBox = styled.div(
  ({ theme }: { theme: Theme }) => `
    padding: ${theme.spacing[7]}px ${theme.spacing[4]}px;  
    margin-top: ${theme.spacing[5]}px;
    border-radius: ${theme.border.radius.large}px;  
    gap: ${theme.spacing[3]}px;
    display: flex;
    align-items: center;
    background-color: ${theme.colors.interactive.background.gray.disabled};
`,
);

const RizeMarketplacePage = ({ showNotification }: RizeMarketplacePageProps): JSX.Element => {
  const { isLoading: isWhatsNewLoading, data: whatsNew } = useMarketplaceWhatsNew();
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint(theme);
  const navigate = useNavigate();

  const isL = ['l', 'xl'].includes(matchedBreakpoint ?? 'l');

  useEffect(trackMarketplaceLoadedSuccess, []);

  const handleProductClick = (product: Result): void => {
    trackDealTileClicked(
      {
        name: product.name,
        category: product.category,
        id: product.id,
        type: product.type,
      },
      'Whats_new',
    );
  };

  return (
    <Box backgroundColor="surface.background.gray.subtle">
      <BladeProvider colorScheme="dark" themeTokens={bladeTheme}>
        <Box paddingBottom="10rem" paddingTop="spacing.9" position="relative" zIndex={1}>
          <BackgroundIntroVideo />
          <Container
            display="flex"
            flexDirection="column"
            alignItems="center"
            justifyContent="center"
          >
            <Link
              variant="button"
              size="medium"
              icon={ArrowLeftIcon}
              iconPosition="left"
              color="white"
              alignSelf="flex-start"
              onClick={(): void => navigate('/dashboard')}
            >
              Back to dashboard
            </Link>
            <StyledRizeLogo />
            <Display
              as="h1"
              size={isL ? 'xlarge' : 'medium'}
              color="surface.text.staticWhite.normal"
              textAlign="center"
              marginTop="spacing.3"
            >
              Marketplace
            </Display>
            <Heading
              as="span"
              size="large"
              weight="regular"
              marginTop="spacing.5"
              textAlign="center"
              color="surface.text.staticWhite.normal"
            >
              Discover Exclusive Product Deals
            </Heading>
            <StyledFadedBox>
              <Text color="surface.text.staticWhite.normal" size="large">
                <i className="i i-sparkles" />
              </Text>
              <Text color="surface.text.staticWhite.normal" size={isL ? 'large' : 'medium'}>
                Curated by Founders; Built for Businesses
              </Text>
            </StyledFadedBox>
          </Container>
        </Box>
        <Box marginTop="-6.5rem">
          <Container position="relative" zIndex={1}>
            <Heading as="h2" color="surface.text.staticWhite.normal" size="large">
              New Rize Deals
            </Heading>
          </Container>
        </Box>
      </BladeProvider>
      <Box>
        <Box>
          <Container
            display={{ base: 'flex', l: 'grid' }}
            gridTemplateColumns="1fr 1fr"
            gap={{ base: 'spacing.4', l: 'spacing.8' }}
            marginTop="spacing.7"
            paddingBottom="spacing.10"
            position="relative"
            overflowX={{ base: 'auto', l: 'visible' }}
            maxWidth={{
              base: 'auto',
              l: `${theme.breakpoints.l}px`,
              xl: `${theme.breakpoints.xl}px`,
            }}
            zIndex={1}
          >
            {isWhatsNewLoading || !whatsNew
              ? new Array(4).fill(null).map((_, i) => <ProductCardTileSkeleton key={i} />)
              : whatsNew.results.map((product) => (
                  <ProductCardTile
                    key={product.slug}
                    name={product.name}
                    slug={product.slug}
                    logoSrc={product.logo_src}
                    category={product.category}
                    excerpt={product.excerpt}
                    offer={product.offer}
                    onClick={(): void => handleProductClick(product)}
                  />
                ))}
          </Container>
        </Box>
      </Box>
      <MarketplaceSearchSection showNotification={showNotification} />
      <RizeFooter source="homepage" />
    </Box>
  );
};

export default withRizeMarketplaceExperimentGuard(
  connect(null, { showNotification })(RizeMarketplacePage),
);
