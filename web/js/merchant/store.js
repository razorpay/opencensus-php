import { createStore, applyMiddleware, compose } from 'redux';

import stateSyncMiddleware, {
  initialStateSyncMiddleware,
  syncInitialReduxState,
} from 'merchant/commonStore/stateSyncMiddleware';
// import { stateSyncMiddleware } from 'merchant/commonStore';
import apiAsyncMiddleware from 'merchant_common/middlewares/apiAsyncMiddleware';

import reducers from './reducers';

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

function configureStore() {
  const store = createStore(
    reducers,
    composeEnhancers(applyMiddleware(apiAsyncMiddleware, stateSyncMiddleware)),
  );

  syncInitialReduxState(store);

  if (module.hot) {
    // Enable Webpack hot module replacement for reducers
    module.hot.accept('./reducers', () => {
      const nextRootReducer = require('./reducers');
      store.replaceReducer(nextRootReducer);
    });
  }

  return store;
}

const store = configureStore();

export const storeWithInitialState = (initialState) => {
  initialStateSyncMiddleware(initialState);
  return createStore(
    reducers,
    initialState,
    composeEnhancers(applyMiddleware(apiAsyncMiddleware, stateSyncMiddleware)),
  );
};

export default store;

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
