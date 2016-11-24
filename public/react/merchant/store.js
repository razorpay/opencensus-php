import { createStore, applyMiddleware } from 'redux'
import thunkMiddleWare from 'redux-thunk'
import apiAsyncMiddleware from 'rzp/middlewares/apiAsyncMiddleware'
import reducers from './reducers'

export default createStore(reducers, applyMiddleware(
  apiAsyncMiddleware,
  thunkMiddleWare
))
