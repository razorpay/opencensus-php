import { useState } from 'react';
import { fetchResellersByProgram } from '../../Programs/queries';
import { useQuery } from '@tanstack/react-query';

const LIST_FETCH_BATCH_SIZE = 25;

const useFetchResellersLinkedToProgram = ({ mode, merchantId, programId }) => {
  const [skip, setSkip] = useState(0);
  const [pageSize, setPageSize] = useState(LIST_FETCH_BATCH_SIZE);

  const {
    isLoading,
    data: resellers,
    refetch,
  } = useQuery({
    queryKey: ['gcms:program:resellers', skip, pageSize, merchantId, programId, mode],
    queryFn: () => fetchResellersByProgram({ skip, mode, merchantId, programId, count: pageSize }),
    refetchOnWindowFocus: false,
    retry: false,
  });

  function handleNext() {
    setSkip(skip + pageSize);
  }
  function handlePrev() {
    setSkip(skip - pageSize);
  }

  function handlePageSizeChange(newPageSize) {
    setSkip(0);
    setPageSize(newPageSize.pageSize);
  }

  return {
    isLoading,
    resellers,
    skip,
    handleNext,
    handlePrev,
    handlePageSizeChange,
    refetch,
  };
};

export default useFetchResellersLinkedToProgram;
