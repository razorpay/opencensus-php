import { createStore, applyMiddleware, compose } from 'redux';
import { apiAsyncMiddleware } from './middleware';
import { getSanitizedState } from './middleware/stateSync';
import reducers from './reducers';

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

function configureStore() {
  const store = createStore(reducers, composeEnhancers(applyMiddleware(apiAsyncMiddleware)));

  return store;
}

const store = configureStore();

export default store;

export const storeWithInitialState = (initialState) => {
  const { reduxInitialState } = getSanitizedState(initialState);
  return createStore(
    reducers,
    reduxInitialState,
    composeEnhancers(applyMiddleware(apiAsyncMiddleware)),
  );
};
