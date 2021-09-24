import { createStore, applyMiddleware, compose } from 'redux';
import apiAsyncMiddleware from 'merchant_common/middlewares/apiAsyncMiddleware';
import reducers from './reducers';

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;
const store = createStore(reducers, composeEnhancers(applyMiddleware(apiAsyncMiddleware)));

export const storeWithInitialState = (initialState) =>
  createStore(reducers, initialState, composeEnhancers(applyMiddleware(apiAsyncMiddleware)));

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
  return store.getState().session.user;
}

export function getPartnerMode() {
  return store.getState().session.partnerMode;
}
