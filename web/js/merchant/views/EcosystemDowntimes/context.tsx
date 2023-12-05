import React, { createContext, useReducer } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import moment from 'moment';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { merge } from 'common/utils/immutable';
import { ACTIONS, SR_QUERY_CACHE_KEY } from 'merchant/views/EcosystemDowntimes/constants';
import {
  fetchOngoingDowntimes,
  fetchResolvedDowntimes,
} from 'merchant/views/EcosystemDowntimes/services';
import { showNotification } from 'merchant_common/reducers/notifications';

import { processOnGoingDowntimes, processPreviousDowntimes } from './helpers';

import type {
  EcosystemDowntimesContextType,
  EcosystemDowntimesActionType,
  EcosystemDowntimesInitialState,
  DowntimeMetaDataType,
  EcosystemDowntimesProviderType,
} from 'merchant/views/EcosystemDowntimes/types';

const initialState: EcosystemDowntimesInitialState = {
  activeDowntimes: {},
  lastUpdatedAt: '',
  previousDowntimes: {},
  focusedInstrument: null,
};

const reducer = (state: EcosystemDowntimesInitialState, action: EcosystemDowntimesActionType) => {
  switch (action.type) {
    case ACTIONS.SET_ACTIVE_DOWNTIMES:
      return merge(state, {
        activeDowntimes: action.payload.activeDowntimes,
        lastUpdatedAt: action.payload.lastUpdatedAt,
      });
    case ACTIONS.SET_FOCUSED_INSTRUMENT:
      return merge(state, {
        focusedInstrument: action.payload.focusedInstrument,
      });
    case ACTIONS.SET_PREVIOUS_DOWNTIMES:
      return merge(state, {
        previousDowntimes: action.payload.previousDowntimes,
      });
    default:
      return state;
  }
};

export const EcosystemDowntimeContext = createContext<EcosystemDowntimesContextType>({
  state: initialState,
  dispatch: () => {},
  refreshData: () => {},
});

const ContextProvider = ({
  children,
  showNotification,
}: EcosystemDowntimesProviderType): JSX.Element => {
  const [state, dispatch] = useReducer(reducer, initialState);
  const queryCache = useQueryClient();

  const handleOnError = (): void => {
    showNotification({
      type: 'error',
      message: 'Something went wrong while fetching downtimes',
    });
  };

  const onOngoingApiSuccess = (data: DowntimeMetaDataType[]) => {
    dispatch({
      type: ACTIONS.SET_ACTIVE_DOWNTIMES,
      payload: {
        activeDowntimes: processOnGoingDowntimes(data),
        lastUpdatedAt: moment().format('hh:mm A'),
      },
    });
  };

  const onResolvedApiSuccess = (data: DowntimeMetaDataType[]) => {
    dispatch({
      type: ACTIONS.SET_PREVIOUS_DOWNTIMES,
      payload: {
        previousDowntimes: processPreviousDowntimes(data),
      },
    });
  };

  const {
    error: ongoingDowntimesError,
    isLoading: isOngoingDowntimeLoading,
    isFetching: isOngoingDowntimeFetching,
    refetch: refetchOngoingDowntimes,
  } = useQuery({
    queryKey: ['ongoing-ecosystemdowntime'],
    queryFn: fetchOngoingDowntimes,
    retry: 2,
    retryDelay: 800,
    cacheTime: 1000 * 60 * 1,
    staleTime: Infinity,
    refetchOnWindowFocus: false,
    refetchOnMount: 'always',
    onError: handleOnError,
    onSuccess: onOngoingApiSuccess,
  });

  const {
    error: previousDowntimesError,
    isLoading: isPreviousDowntimesLoading,
    isFetching: isPreviousDowntimesFetching,
    refetch: refetchPreviousDowntimes,
  } = useQuery({
    queryKey: ['previous-ecosystemdowntime'],
    queryFn: () => {
      return fetchResolvedDowntimes();
    },
    retry: 2,
    retryDelay: 800,
    cacheTime: 1000 * 60 * 1,
    staleTime: Infinity,
    refetchOnWindowFocus: false,
    refetchOnMount: 'always',
    onError: handleOnError,
    onSuccess: onResolvedApiSuccess,
  });

  const refreshData = () => {
    queryCache.invalidateQueries({
      queryKey: [SR_QUERY_CACHE_KEY],
    });

    refetchOngoingDowntimes();

    refetchPreviousDowntimes();
  };

  const propsToBeExposed = {
    state: {
      ...state,
      isOngoingDowntimesError: !!ongoingDowntimesError,
      isOngoingDowntimeLoading,
      isOngoingDowntimeFetching,
      isPreviousDowntimesError: !!previousDowntimesError,
      isPreviousDowntimesLoading,
      isPreviousDowntimesFetching,
    },
    dispatch,
    refreshData,
  };

  return (
    <EcosystemDowntimeContext.Provider value={propsToBeExposed}>
      {children}
    </EcosystemDowntimeContext.Provider>
  );
};

const EcosystemDowntimeProvider = compose(
  connect(null, {
    showNotification,
  }),
)(ContextProvider);

export { EcosystemDowntimeProvider };
