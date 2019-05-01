import { createStore, applyMiddleware } from 'redux';
import apiAsyncMiddleware from 'rzp/middlewares/apiAsyncMiddleware';
import reducers from 'merchantLA/reducers';

const store = createStore(
  reducers,
  window.__REDUX_DEVTOOLS_EXTENSION__ && window.__REDUX_DEVTOOLS_EXTENSION__(),
  applyMiddleware(apiAsyncMiddleware)
);

export default store;

export function getMode() {
  return store.getState().session.mode;
}
