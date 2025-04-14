import React, { useState, useEffect, useCallback } from 'react';
import {
  Box,
  Button,
  useToast,
  ArrowRightIcon,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
} from '@razorpay/blade/components';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import SearchContent from './SearchContent';
import { useNavigate } from 'react-router-dom';
import OnboardingDetailsContent from './OnboardingDetailsContent';
import { BodyOverflowStyle } from './styles';
import debounce from 'lodash/debounce';
import { useInfiniteQuery, useQueryClient } from '@tanstack/react-query';
import { graphqlRequest } from '@federated/apps/shell/graphql';

import { SALES_ONBOARDED_MERCHANTS } from 'apps/pos/src/services/queries/SalesDashboard';
import {
  SalesOnboardedMerchants,
  SalesOnboardedMerchant,
  OnboardingStatusTypes,
} from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { GraphQLErrorResponseType } from 'apps/pos/src/app/types/common';
import useOnboardingStore from 'apps/pos/src/bootstrap/Store/index';
import {
  ASSISTED_ONBOARDING,
  PARTNER_ASSISTED_ONBOARDING,
} from 'apps/pos/src/app/constants/SalesAssistedOnboarding';
import { MINIMUM_SEARCH_INPUT_LENGTH } from './constants';

interface Range {
  startDate: number;
  endDate: number;
}
interface SearchContainerProps {
  isSearchOpen: boolean;
  setIsSearchOpen: (isSearchOpen: boolean) => void;
  dateRange: Range;
}

const PAGE_SIZE = 10;
const QUERY_KEY = 'salesSearch';

const onboardingDetailsCTA = (
  status: OnboardingStatusTypes | undefined,
  isMobile: boolean,
  handleOnboardingDetailsCTAClick: () => void,
) => {
  switch (status) {
    case 'pending':
      return (
        <Button
          icon={ArrowRightIcon}
          iconPosition="right"
          isFullWidth={isMobile}
          onClick={handleOnboardingDetailsCTAClick}
        >
          Complete now
        </Button>
      );
    case 'needs_clarification':
      return (
        <Button
          icon={ArrowRightIcon}
          iconPosition="right"
          isFullWidth={isMobile}
          onClick={handleOnboardingDetailsCTAClick}
        >
          Resolve now
        </Button>
      );
    default:
      return null;
  }
};

const SearchContainer = ({ isSearchOpen, setIsSearchOpen, dateRange }: SearchContainerProps) => {
  const { isMobile } = useScreen();
  const { isPosEkycAgent } = useOnboardingStore();
  const toast = useToast();
  const queryCache = useQueryClient();
  const navigate = useNavigate();

  const handleError = (response: GraphQLErrorResponseType): void => {
    toast.show({
      color: 'negative',
      content: `${response?.message || 'Something went wrong. Please try again.'}`,
      autoDismiss: true,
    });
  };

  const [showOnboardingDetails, setShowOnboardingDetails] = useState(false);
  const [searchText, setSearchText] = useState('');
  const [isDebouncing, setIsDebouncing] = useState(false);
  const [page, setPage] = useState(0);
  const [enabled, setEnabled] = useState(false);
  const [onboardingDetails, setOnboardingDetails] = useState<SalesOnboardedMerchant | null>(null);

  const { data, fetchNextPage, hasNextPage, isFetching } = useInfiniteQuery<
    SalesOnboardedMerchants | null,
    GraphQLErrorResponseType
  >({
    queryKey: [QUERY_KEY, isPosEkycAgent],
    queryFn: async ({ pageParam = 0 }) => {
      const { salesOnboardedMerchants: response } = await graphqlRequest<
        'salesOnboardedMerchants',
        SalesOnboardedMerchants,
        GraphQLErrorResponseType
      >({
        document: SALES_ONBOARDED_MERCHANTS,
        variables: {
          search: searchText || '',
          limit: PAGE_SIZE,
          offset: pageParam * PAGE_SIZE,
          startDate: dateRange?.startDate,
          endDate: dateRange?.endDate,
          status: 'all',
          signupCampaign: isPosEkycAgent ? PARTNER_ASSISTED_ONBOARDING : ASSISTED_ONBOARDING,
        },
      });

      if (response?.__typename === 'SalesOnboardedMerchants')
        return response as SalesOnboardedMerchants;
      else if (response?.__typename === 'SalesOnboardedMerchantsError') {
        handleError(response as GraphQLErrorResponseType);
      }

      return null;
    },
    getNextPageParam: (lastPage) => {
      if (!lastPage?.hasMore) return undefined;
      return page + 1;
    },
    staleTime: Infinity,
    retry: false,
    networkMode: 'always',
    refetchOnWindowFocus: false,
    refetchOnMount: false,
    enabled: enabled,
    onError: (error) => {
      handleError(error);
    },
  });

  const searchResults = (data?.pages ?? []).map((item) => item).filter((item) => item !== null);
  const merchants: SalesOnboardedMerchant[] =
    searchText.trim().length >= MINIMUM_SEARCH_INPUT_LENGTH
      ? searchResults.flatMap((result) => result.merchants)
      : [];

  const isOnboardingDetailsCTA = ['pending', 'needs_clarification'].includes(
    onboardingDetails?.status?.toLowerCase() ?? '',
  );

  const handleSearchClose = () => {
    setIsSearchOpen(false);
    setEnabled(false);
    setSearchText('');
    setShowOnboardingDetails(false);
    void queryCache.removeQueries({ queryKey: [QUERY_KEY, isPosEkycAgent] });
  };

  const handleNewSearch = () => {
    void queryCache.removeQueries({ queryKey: [QUERY_KEY, isPosEkycAgent] });
    setEnabled(true);
    setIsDebouncing(false);
    setPage(0);
    void fetchNextPage({ pageParam: 0 });
  };

  const handleNextPage = () => {
    if (searchText.trim().length < MINIMUM_SEARCH_INPUT_LENGTH || isFetching || !hasNextPage)
      return;
    setPage(page + 1);
    void fetchNextPage({ pageParam: page + 1 });
  };

  const debouncedHandleNewSearch = useCallback(
    debounce(() => {
      handleNewSearch();
    }, 800),
    [],
  );

  useEffect(() => {
    if (showOnboardingDetails) {
      setIsSearchOpen(isMobile);
    }
  }, [isMobile, showOnboardingDetails]);

  useEffect(() => {
    const trimmedSearchText = searchText.trim();

    if (trimmedSearchText.length >= MINIMUM_SEARCH_INPUT_LENGTH) {
      debouncedHandleNewSearch();
      setIsDebouncing(true);
    } else if (trimmedSearchText.length === 0) {
      void queryCache.removeQueries({ queryKey: [QUERY_KEY, isPosEkycAgent] });
      setEnabled(false);
    }

    return () => {
      debouncedHandleNewSearch.cancel();
    };
  }, [searchText]);

  const handleOnboardingDetailsCTAClick = () => {
    let MID = onboardingDetails?.merchantId;
    navigate(`onboarding/${MID}`);
  };

  if (isMobile && isSearchOpen) {
    return (
      <Box
        width="100%"
        height="100%"
        position="absolute"
        backgroundColor={'surface.background.gray.intense'}
        zIndex={10}
        paddingX={'spacing.4'}
        marginTop={'spacing.4'}
      >
        <BodyOverflowStyle isSearchOpen={isMobile && isSearchOpen} />
        <SearchContent
          searchText={searchText}
          setSearchText={setSearchText}
          handleSearchClose={handleSearchClose}
          setShowOnboardingDetails={setShowOnboardingDetails}
          setOnboardingDetails={setOnboardingDetails}
          searchResults={merchants}
          isFetching={(isFetching || isDebouncing) && page === 0}
          handleNextPage={handleNextPage}
        />

        <BottomSheet
          isOpen={showOnboardingDetails}
          onDismiss={() => {
            setShowOnboardingDetails(false);
          }}
          snapPoints={[0.5, 0.5, 0.5]}
        >
          <BottomSheetHeader title="Onboarding Details" />
          <BottomSheetBody>
            <OnboardingDetailsContent
              onboardingDetails={onboardingDetails as SalesOnboardedMerchant}
            />
          </BottomSheetBody>
          {isOnboardingDetailsCTA && (
            <BottomSheetFooter>
              <Box
                display="flex"
                flexDirection="row"
                alignItems="center"
                justifyContent="space-between"
              >
                {onboardingDetailsCTA(
                  onboardingDetails?.status?.toLowerCase() as OnboardingStatusTypes,
                  isMobile,
                  handleOnboardingDetailsCTAClick,
                )}
              </Box>
            </BottomSheetFooter>
          )}
        </BottomSheet>
      </Box>
    );
  }

  return (
    <>
      <Modal isOpen={isSearchOpen} onDismiss={handleSearchClose} size="medium">
        <ModalBody>
          <SearchContent
            searchText={searchText}
            setSearchText={setSearchText}
            handleSearchClose={handleSearchClose}
            setShowOnboardingDetails={setShowOnboardingDetails}
            setOnboardingDetails={setOnboardingDetails}
            searchResults={merchants}
            isFetching={(isFetching || isDebouncing) && page === 0}
            handleNextPage={handleNextPage}
          />
        </ModalBody>
      </Modal>

      <Modal isOpen={showOnboardingDetails} onDismiss={handleSearchClose} size="small">
        <ModalHeader title="Onboarding Details" />
        <ModalBody>
          <OnboardingDetailsContent
            onboardingDetails={onboardingDetails as SalesOnboardedMerchant}
          />
        </ModalBody>
        <ModalFooter>
          <Box display="flex" flexDirection="row" alignItems="center" justifyContent="flex-end">
            <Button
              onClick={() => {
                setIsSearchOpen(true);
                setShowOnboardingDetails(false);
              }}
              size="medium"
              variant="tertiary"
              marginRight={'spacing.4'}
            >
              Back to search
            </Button>

            {onboardingDetailsCTA(
              onboardingDetails?.status?.toLowerCase() as OnboardingStatusTypes,
              isMobile,
              handleOnboardingDetailsCTAClick,
            )}
          </Box>
        </ModalFooter>
      </Modal>
    </>
  );
};

export default SearchContainer;
