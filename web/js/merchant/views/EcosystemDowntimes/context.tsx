import React, { createContext, useReducer } from 'react';
import { useQuery } from 'react-query';
import { ACTIONS } from 'merchant/views/EcosystemDowntimes/constants';
import {
  fetchOngoingDowntimes,
  fetchResolvedDowntimes,
} from 'merchant/views/EcosystemDowntimes/services';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';
import moment from 'moment';
import type {
  EcosystemDowntimesContextType,
  EcosystemDowntimesProviderType,
  EcosystemDowntimesActionType,
  EcosystemDowntimesInitialState,
  DowntimeMetaDataType,
} from 'merchant/views/EcosystemDowntimes/types';
import { merge } from 'common/utils/immutable';
import { processOnGoingDowntimes, processPreviousDowntimes } from './helpers';

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

export const EcosystemDowntimeProvider = ({
  children,
}: EcosystemDowntimesProviderType): JSX.Element => {
  const [state, dispatch] = useReducer(reducer, initialState);
  const snackbar = useSnackbar();

  const handleOnError = (err: { error: string }): void => {
    if (err?.error) {
      snackbar.error(err?.error);
    }
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
  } = useQuery('ongoing-ecosystemdowntime', fetchOngoingDowntimes, {
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
  } = useQuery(
    'previous-ecosystemdowntime',
    () => {
      return fetchResolvedDowntimes();
    },
    {
      retry: 2,
      retryDelay: 800,
      cacheTime: 1000 * 60 * 1,
      staleTime: Infinity,
      refetchOnWindowFocus: false,
      refetchOnMount: 'always',
      onError: handleOnError,
      onSuccess: onResolvedApiSuccess,
    },
  );

  const refreshData = () => {
    refetchOngoingDowntimes();
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
