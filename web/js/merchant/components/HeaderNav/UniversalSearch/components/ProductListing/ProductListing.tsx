import { Box, SearchIcon, Text } from '@razorpay/blade/components';
import BodyScrollLock from 'common/components/BodyScrollLock';
import { SlideTransition } from 'common/components/Transition';
import {
  CommonStateProps,
  ObjType,
  ProductItem,
  ProductType,
} from 'merchant/components/HeaderNav/UniversalSearch/typings';
import { getResultsForAnalytics, trackSearchResultClicked } from 'merchant/components/HeaderNav/UniversalSearch/utils';
import React from 'react';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import {
  Icon,
  ProductListingOverlay,
  ProductTag,
  StyledList,
  StyledProductListingContainer,
  StyledText,
} from './styled';

type SearchResult = {
  isPopular: boolean;
  products: ProductType[];
};

const ProductListing = ({
  searchResults: { isPopular, products },
  handleClick,
}: {
  searchResults: SearchResult;
  handleClick: (args: ProductItem, trackProps: ObjType) => void;
}): JSX.Element => {
  return products.length ? (
    <>
      {isPopular ? (
        <Text
          size="small"
          weight="semibold"
          margin={['spacing.3', 'spacing.3', 'spacing.0']}
          color="surface.text.gray.muted"
        >
          Popular searches
        </Text>
      ) : null}
      <Box display="flex" flexDirection="column">
        {products.map(
          ({ item }, index): JSX.Element => (
            <StyledList
              onClick={handleClick.bind(null, item, {
                optionName: item.title,
                optionChosen: index + 1,
                optionSet: products.length ? 1 : 0,
                optionSetTotal: products.length,
                optionGroup: item.group?.length ? item.group[0].replace('in: ', '') : null,
                results: getResultsForAnalytics(products),
              })}
              key={`product-listing-${index}`}
            >
              <Icon className={`i ${item.icon}`} />
              <StyledText>
                <Text size="medium">{item.title}</Text>
              </StyledText>
              {item.group?.length ? (
                <ProductTag>
                  <Text size="small" weight="semibold" color="surface.text.gray.muted">
                    {item.group[0]}
                  </Text>
                </ProductTag>
              ) : null}
            </StyledList>
          ),
        )}
      </Box>
    </>
  ) : (
    <Box
      display="flex"
      flexDirection="column"
      gap="11px"
      alignItems="center"
      padding={['15px', '13px']}
    >
      <SearchIcon color="interactive.icon.gray.normal" size="large" />
      <Box display="flex" flexDirection="column" gap="4px" alignItems="center">
        <Text weight="semibold" color="surface.text.gray.muted">
          No search results found
        </Text>
        <Text size="small" textAlign="center" color="surface.text.gray.muted">
          You can search for payment products, Account & Settings, and more
        </Text>
      </Box>
    </Box>
  );
};

const ProductListingWrapper = React.forwardRef(
  (
    {
      history,
      setSearch,
      setFocussed,
      isMobile,
      isDeviceInBreakpoint,
      show,
      searchResults,
      searchQuery,
    }: CommonStateProps &
      Partial<RouteComponentProps> & {
        searchResults: SearchResult;
        isMobile: boolean;
      },
    ref,
  ): JSX.Element | null => {
    const handleClick = ({ url }, trackProps) => {
      trackSearchResultClicked({ ...trackProps, queryTyped: searchQuery });
      history?.push(url);
      setFocussed(false);
      setSearch('');
    };

    if (isMobile) {
      return (
        <div ref={ref as React.RefObject<HTMLDivElement>} data-testid="universal-search-results">
          {show && (
            <>
              <BodyScrollLock />
              <SlideTransition duration={400} in={show} appear unmountOnExit slideFrom="bottom">
                <ProductListingOverlay>
                  <Box display="flex" flexDirection="column" padding="spacing.4" gap="spacing.3">
                    <ProductListing handleClick={handleClick} searchResults={searchResults} />
                  </Box>
                </ProductListingOverlay>
              </SlideTransition>
            </>
          )}
        </div>
      );
    }

    if (!show) return null;

    return (
      <StyledProductListingContainer
        isMobile={isMobile}
        isDeviceInBreakpoint={isDeviceInBreakpoint}
        data-testid="universal-search-results"
      >
        <ProductListing handleClick={handleClick} searchResults={searchResults} />
      </StyledProductListingContainer>
    );
  },
);

export default ProductListingWrapper;
