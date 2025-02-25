import { createStore, applyMiddleware, compose } from 'redux';
import apiAsyncMiddleware from 'merchant_common/middlewares/apiAsyncMiddleware';
import reducers from 'merchantLA/reducers';
import { useStore as shellZustandStore } from '@federated/apps/shell/commonStore';
import {
  syncShellZustandWithReduxMiddleware,
  attachZustandToReduxSyncAction,
} from 'common/utils/store-sync';

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

const store = createStore(
  attachZustandToReduxSyncAction(reducers),
  composeEnhancers(
    applyMiddleware(apiAsyncMiddleware, syncShellZustandWithReduxMiddleware(shellZustandStore)),
  ),
);

export default store;

export function getMode() {
  return store.getState().session.mode;
}

export function getOrg() {
  return store.getState().session.org;
}
