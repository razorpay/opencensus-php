import React, { useEffect, useRef, useState } from 'react';

import { Box, SearchIcon, Spinner } from '@razorpay/blade/components';
import { TextContainer } from './styled';

import { merchantFetch } from 'merchant/utils/ajax';

import { InfiniteLoaderProps, QueryParams } from './types';

const DEFAULT_PAGE_SIZE = 20;

const InfiniteScroll = <T extends Record<string, unknown>>({
  searchText,
  pageSize,
  url,
  itemsKey,
  rowRenderer,
  spinner,
  setHasErrorInFetchingProducts,
  appType,
}: InfiniteLoaderProps<T>): JSX.Element => {
  const [data, setData] = useState<T[]>([]);
  const [hasNext, setHasNext] = useState(true);
  const [isFetching, setIsFetching] = useState(false);
  const [error, setError] = useState<Record<string, string[]> | null>(null);
  const [next, setNext] = useState<string | null>(null);
  const element = useRef<HTMLDivElement>(null);

  const fetchFn = (overrideParams?: QueryParams): Promise<any> => {
    const params: QueryParams = {
      count: pageSize || DEFAULT_PAGE_SIZE,
    };
    if (searchText) params.search_text = searchText;
    if (next) params.cursor = overrideParams?.cursor || next;

    if (appType) {
      params.app_type = appType;
    }

    return merchantFetch({
      url,
      method: 'get',
      params,
    });
  };

  const updatePaginationAndItemsState = (response) => {
    const { data: apiData } = response;
    const items = apiData?.[itemsKey];
    setData([...data, ...items]);
    setHasNext(apiData?.page_info?.has_next_page);
    setNext(apiData?.page_info?.end_cursor);
  };

  const fetchData = (overrideParams?: QueryParams) => {
    setIsFetching(true);
    setError(null);
    if (setHasErrorInFetchingProducts) {
      setHasErrorInFetchingProducts(false);
    }
    fetchFn(overrideParams)
      .then((response) => {
        updatePaginationAndItemsState(response);
      })
      .catch((err) => {
        setError(err);
        if (setHasErrorInFetchingProducts) {
          setHasErrorInFetchingProducts(true);
        }
      })
      .finally(() => setIsFetching(false));
  };

  const loadMore = (entries: IntersectionObserverEntry[]) => {
    const entry = entries[0];
    if (entry.isIntersecting && hasNext && !isFetching && !error) {
      fetchData();
    }
  };
  const intersectionObserver = new IntersectionObserver(loadMore);

  useEffect(() => {
    let ref;

    if (element?.current) {
      ref = element.current;
      intersectionObserver.observe(element.current);
    }
    return () => {
      if (ref) {
        intersectionObserver.unobserve(ref);
      }
    };
  }, [next, isFetching, searchText]);

  useEffect(() => {
    setNext(null);
    setData([]);
    setHasNext(true);
  }, [searchText]);

  return (
    <>
      {data?.length ? data.map((item) => rowRenderer(item)) : null}
      <div ref={element}>
        <Box paddingY="spacing.4" />
      </div>

      {isFetching ? (
        <Box display="flex" alignItems="center" justifyContent="center" marginY="spacing.4">
          {spinner ? spinner : <Spinner accessibilityLabel="spinner" />}
        </Box>
      ) : (
        <TextContainer>
          {error ? (
            <p>{error?.errors?.[0] || 'Something went wrong'}</p>
          ) : data?.length === 0 ? (
            <>
              <SearchIcon color="feedback.icon.information.intense" size="large" />
              <p>No results found</p>
            </>
          ) : null}
        </TextContainer>
      )}
    </>
  );
};

export default InfiniteScroll;
