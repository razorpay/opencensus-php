import { useEffect, useState, useCallback } from 'react';
import { useQuery } from '@tanstack/react-query';
import { ListApiResponse } from 'merchant/views/GCMS/shared/types';
import { Program as ProgramType } from 'merchant/views/GCMS/Programs/types';
import {
  fetchPrograms,
  fetchProgramImages,
  LIST_FETCH_BATCH_SIZE,
} from 'merchant/views/GCMS/Programs/queries';

const LIST_FETCH_SIZE = 10;

const useFetchPrograms = ({ mode }) => {
  const [paginationState, setPaginationState] = useState({
    skip: 0,
    count: LIST_FETCH_SIZE,
  });

  const {
    isLoading,
    data: programs,
    refetch: refetchPrograms,
  } = useQuery<ListApiResponse<ProgramType>, Error>({
    queryKey: ['gcms:programs', mode, paginationState],
    queryFn: () => fetchPrograms({ ...paginationState, mode }),
    refetchOnWindowFocus: false,
  });

  const { isLoading: isImageLoading, data: programImages } = useQuery({
    queryKey: ['gcms:programs:images', programs],
    queryFn: () => fetchProgramImages({ programs, mode }),
    refetchOnWindowFocus: false,
    enabled: Boolean(programs),
  });

  function refetch(){
    setPaginationState((prevState) => ({...prevState, skip: 0}))
    refetchPrograms()
  }

  function changePageSize(value) {
    setPaginationState({
      skip: 0,
      count: value,
    });
  }

  const next = useCallback(() => {
    const skipValue = paginationState.skip + paginationState.count;
    setPaginationState(prevState => ({
      ...prevState,
      skip: skipValue,
    }));
  }, [paginationState.count, paginationState.skip]);

  const prev = useCallback(() => {
    const skipValue = Math.max(paginationState.skip - paginationState.count,0);
    setPaginationState(prevState => ({
      ...prevState,
      skip: skipValue,
    }));
  }, [paginationState.count, paginationState.skip]);

  return {
    isLoading,
    programs,
    next,
    prev,
    skip: paginationState.skip,
    count: paginationState.count,
    changePageSize,
    programImages,
    isImageLoading,
    refetch,
  };
};

export default useFetchPrograms;
