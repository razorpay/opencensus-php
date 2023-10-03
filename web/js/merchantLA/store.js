import { createStore, applyMiddleware } from 'redux';
import apiAsyncMiddleware from 'merchant_common/middlewares/apiAsyncMiddleware';
import reducers from 'merchantLA/reducers';

const store = createStore(reducers, applyMiddleware(apiAsyncMiddleware));

export default store;

export function getMode() {
  return store.getState().session.mode;
}

export function getOrg() {
  return store.getState().session.org;
}
