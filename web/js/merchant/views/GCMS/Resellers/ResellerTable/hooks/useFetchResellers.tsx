import { useState } from 'react';
import { fetchResellers, fetchUnmappedResellers } from '../../queries';
import { useQuery } from '@tanstack/react-query';
import { RESELLER_SERVICE } from '../constants';

const LIST_FETCH_BATCH_SIZE = 25;

const useFetchResellers = ({ mode, merchantId, programId, service, refetchQuery }) => {
  const [skip, setSkip] = useState(0);
  const [pageSize, setPageSize] = useState(LIST_FETCH_BATCH_SIZE);
  const [resellerName, setResellerName] = useState('');
  const [resellerStatus, setResellerStatus] = useState('all');

  const {
    isLoading,
    data: resellers,
    refetch,
  } = useQuery({
    queryKey: ['gcms:resellers', skip, resellerName, resellerStatus, pageSize, refetchQuery, mode],
    queryFn: async () => {
      if (service === RESELLER_SERVICE.ALL_RESELLERS) {
        return fetchResellers({
          skip,
          resellerName,
          count: pageSize,
          resellerStatus,
          mode,
          merchantId,
        });
      } else {
        return fetchUnmappedResellers({
          skip,
          count: pageSize,
          resellerName,
          mode,
          merchantId,
          programId: programId.split('iprog_')[1],
        });
      }
    },
    onError: function (e) {},
    onSuccess: () => {},
    refetchOnWindowFocus: false,
    retry: false,
    cacheTime: 0,
  });

  function handleNext() {
    setSkip(skip + pageSize);
  }
  function handlePrev() {
    setSkip(skip - pageSize);
  }

  function onPageSizeChange(nextPageSize) {
    console.log(nextPageSize);
    setSkip(0);
    setPageSize(nextPageSize.pageSize);
  }

  const handleSearch = ({ resellerName, status }) => {
    setResellerName(resellerName);
    setResellerStatus(status);
    setSkip(0);
  };

  return {
    isLoading,
    resellers,
    skip,
    handleNext,
    handlePrev,
    setResellerName,
    setResellerStatus,
    resellerName,
    resellerStatus,
    handleSearch,
    onPageSizeChange,
    pageSize,
    refetch,
  };
};

export default useFetchResellers;
