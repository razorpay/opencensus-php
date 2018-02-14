import { createStore, applyMiddleware } from 'redux';
import apiAsyncMiddleware from 'rzp/middlewares/apiAsyncMiddleware';
import reducers from './reducers';

const store = createStore(reducers, applyMiddleware(apiAsyncMiddleware));

export default store;

export function getMode() {
  return store.getState().session.mode;
}