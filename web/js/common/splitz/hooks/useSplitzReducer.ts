import { useReducer } from 'react';
import {
  splitzServiceActions,
  splitzServiceInitialState,
  splitzServiceReducer,
} from 'common/splitz/reducer/splitzSlice';
import { Dispatch, bindActionCreators } from 'redux';
import { SplitzServiceActionType, UseSplitzReducerReturnType } from 'common/splitz/types';

export const useSplitzReducer = (): UseSplitzReducerReturnType => {
  const [state, dispatch] = useReducer(splitzServiceReducer, splitzServiceInitialState);

  const actions: SplitzServiceActionType = bindActionCreators(
    splitzServiceActions,
    dispatch as Dispatch,
  );

  return { ...state, ...actions };
};
