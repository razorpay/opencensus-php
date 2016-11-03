import { createStore, applyMiddleware } from 'redux'
import thunkMiddleWare from 'redux-thunk'
// import actionMiddleware from './middlewares/actionMiddleware'
import reducers from './reducers'

export default createStore(reducers, applyMiddleware(
  // actionMiddleware,
  thunkMiddleWare,
))
