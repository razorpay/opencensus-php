/* eslint-disable import/no-import-module-exports */
import { useStore as shellZustandStore } from '@federated/apps/shell/commonStore';
import {
  syncShellZustandWithReduxMiddleware,
  attachZustandToReduxSyncAction,
} from 'common/utils/store-sync';
import apiAsyncMiddleware from 'merchant_common/middlewares/apiAsyncMiddleware';
import { createStore, applyMiddleware, compose } from 'redux';

import reducers from './reducers';

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

const rootReducer = attachZustandToReduxSyncAction(reducers);

function configureStore() {
  const store = createStore(
    rootReducer,
    composeEnhancers(
      applyMiddleware(apiAsyncMiddleware, syncShellZustandWithReduxMiddleware(shellZustandStore)),
    ),
  );

  if (module.hot) {
    // Enable Webpack hot module replacement for reducers.
    module.hot.accept('./reducers', () => {
      const nextRootReducer = require('./reducers');
      store.replaceReducer(nextRootReducer);
    });
  }

  return store;
}

const store = configureStore();

export const storeWithInitialState = (initialState) => {
  const storeWithInitialData = createStore(
    rootReducer,
    initialState,
    composeEnhancers(
      applyMiddleware(
        apiAsyncMiddleware,
        syncShellZustandWithReduxMiddleware(shellZustandStore, initialState),
      ),
    ),
  );
  return storeWithInitialData;
};

export function getMode() {
  if (store.getState().session.isUsingPartnerMode) {
    return getPartnerMode();
  }
  return store.getState().session.mode;
}

export function getOrg() {
  return store.getState().session.org;
}

export function getUser() {
  return store?.getState()?.session?.user;
}

export function getPartnerMode() {
  return store.getState().session.partnerMode;
}

export default store;
