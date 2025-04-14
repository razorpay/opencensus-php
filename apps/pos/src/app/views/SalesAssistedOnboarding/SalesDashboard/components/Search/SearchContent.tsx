import React, { useState, useEffect, useMemo } from 'react';
import {
  Divider,
  ChipGroup,
  Chip,
  Text,
  Box,
  SearchInput,
  ChevronLeftIcon,
  IconButton,
} from '@razorpay/blade/components';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import useIntersectionObserver from 'apps/pos/src/app/utils/hooks/useIntersectionObserver';
import SearchItem from './SearchItem';
import SearchSkeleton from './SearchSkeleton';
import SearchEmptyState from './SearchEmptyState';
import { SalesOnboardedMerchant } from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { getRecentSearches, storeRecentSearches, searchMerchants } from './helper';
import { MINIMUM_SEARCH_CHARACTERS_MESSAGE, MINIMUM_SEARCH_INPUT_LENGTH } from './constants';

interface SearchContainerProps {
  searchText: string;
  setSearchText: React.Dispatch<React.SetStateAction<string>>;
  handleSearchClose: () => void;
  setShowOnboardingDetails: React.Dispatch<React.SetStateAction<boolean>>;
  setOnboardingDetails: React.Dispatch<React.SetStateAction<SalesOnboardedMerchant | null>>;
  searchResults: SalesOnboardedMerchant[];
  isFetching: boolean;
  handleNextPage: () => void;
}

type ExtendedSalesOnboardedMerchant = SalesOnboardedMerchant & {
  primaryText?: string;
};

enum FilterType {
  ALL = 'all',
  MERCHANT_NAME = 'merchantName',
  BUSINESS_NAME = 'businessName',
}

const SearchContent = ({
  searchText = '',
  setSearchText,
  handleSearchClose,
  setShowOnboardingDetails,
  setOnboardingDetails,
  searchResults,
  isFetching,
  handleNextPage,
}: SearchContainerProps) => {
  const { isMobile } = useScreen();
  const recentSearches = getRecentSearches();

  const [filterType, setFilterType] = useState(FilterType.ALL);
  const [filteredSearchResults, setFilteredSearchResults] = useState<SalesOnboardedMerchant[]>([]);

  useEffect(() => {
    if (filterType !== FilterType.ALL) {
      const filteredResults = searchMerchants(searchText, filterType, searchResults);
      setFilteredSearchResults(filteredResults);
    }
  }, [filterType]);

  const handleFilterChange = (value: FilterType[]) => {
    setFilterType(value[0]);
  };

  const handleRecentSearchClick = (item: SalesOnboardedMerchant) => {
    setShowOnboardingDetails(true);
    setOnboardingDetails(item);
  };

  const handleSearchResultClick = (item: SalesOnboardedMerchant) => {
    const primaryText =
      filterType === FilterType.BUSINESS_NAME ? item?.billingLabel : item?.merchantName;

    setShowOnboardingDetails(true);
    setOnboardingDetails(item);
    storeRecentSearches({ ...item, primaryText: primaryText || '' });
  };

  const setTargetRef = useIntersectionObserver({ callback: handleNextPage });

  const renderSearchItem = (
    item: ExtendedSalesOnboardedMerchant,
    index: number,
    isRecentSearch = false,
  ) => {
    const primaryText = isRecentSearch
      ? item?.primaryText
      : filterType === FilterType.BUSINESS_NAME
      ? item?.billingLabel
      : item?.merchantName;
    const secondaryText = item?.merchantId;

    return (
      <SearchItem
        key={index}
        isRecentSearch={isRecentSearch}
        primaryText={primaryText || ''}
        secondaryText={secondaryText || ''}
        searchText={searchText}
        onClick={() =>
          isRecentSearch ? handleRecentSearchClick(item) : handleSearchResultClick(item)
        }
      />
    );
  };

  const SearchResults = useMemo(() => {
    const noResultsFound =
      searchText.trim().length >= MINIMUM_SEARCH_INPUT_LENGTH && searchResults?.length == 0;
    const isEmptySearch =
      searchText.trim().length < MINIMUM_SEARCH_INPUT_LENGTH && recentSearches.length == 0;
    const updatedSearchResults =
      filterType === FilterType.ALL ? searchResults : filteredSearchResults;

    if (isFetching) {
      return <SearchSkeleton />;
    } else if (isEmptySearch || noResultsFound) {
      return <SearchEmptyState noResultsFound={noResultsFound} />;
    } else {
      return <>{updatedSearchResults?.map((item, index) => renderSearchItem(item, index))}</>;
    }
  }, [isFetching, searchResults, searchText, filteredSearchResults, filterType]);

  return (
    <Box
      display={'flex'}
      justifyContent={'left'}
      alignItems={'flex-start'}
      flexDirection={'column'}
      height={isMobile ? '90vh' : '70vh'}
    >
      <Box
        display={'flex'}
        flexDirection={'row'}
        justifyContent={'left'}
        alignItems={'center'}
        width="100%"
        marginBottom={'spacing.5'}
      >
        {isMobile && (
          <IconButton
            icon={() => <ChevronLeftIcon color="surface.icon.staticBlack.normal" size="large" />}
            size="large"
            onClick={handleSearchClose}
            accessibilityLabel="close-search-button"
            emphasis="intense"
          />
        )}

        <Box width="100%" marginLeft={'spacing.2'}>
          <SearchInput
            placeholder="Search"
            name="search"
            size="medium"
            label=""
            autoFocus
            value={searchText}
            onChange={(e) => setSearchText(`${e?.value}`)}
            onClearButtonClick={() => setSearchText('')}
            helpText={
              searchText.trim().length < MINIMUM_SEARCH_INPUT_LENGTH
                ? MINIMUM_SEARCH_CHARACTERS_MESSAGE
                : ''
            }
          />
        </Box>
      </Box>

      {searchResults?.length !== 0 && (
        <ChipGroup
          accessibilityLabel="Filter Type"
          value={filterType}
          onChange={({ values }) => handleFilterChange(values as FilterType[])}
          marginBottom={'spacing.4'}
        >
          <Chip value={FilterType.ALL}>All</Chip>
          <Chip value={FilterType.MERCHANT_NAME} marginX={'spacing.2'}>
            Merchant Name
          </Chip>
          <Chip value={FilterType.BUSINESS_NAME}>Business Name</Chip>
        </ChipGroup>
      )}

      <Box width="100%" overflow={'auto'}>
        <>
          {recentSearches.length !== 0 && (
            <>
              <Text color="surface.text.gray.muted" size="small" weight="medium">
                Recent searches
              </Text>
              {recentSearches.map((item, index) => renderSearchItem(item, index, true))}
              <Divider width={'100%'} thickness="thick" marginBottom={'spacing.2'} />
            </>
          )}
          {SearchResults}
        </>

        <Box
          ref={(ref) => setTargetRef(ref)}
          height={'spacing.4'}
          backgroundColor={'transparent'}
        />
      </Box>
    </Box>
  );
};

export default React.memo(SearchContent);
