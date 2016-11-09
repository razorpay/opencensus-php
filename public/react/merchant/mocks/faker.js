import DFaqApi from 'dfaqapi'
import customers from './customers'
import plans from './plans'
import subscriptions from './subscriptions'

let fakeApi = new DFaqApi({
  factories: {
    plans,
    customers,
    subscriptions
  }
})

fakeApi.createList('customers', 10)
fakeApi.createList('plans', 5)
fakeApi.createList('subscriptions', 3)

fakeApi.get('/test/subscriptions', (db, request) => {
  return {
    success: true,
    data: {
      count: 10,
      items: db.getCollection('subscriptions').data
    }
  }
})

fakeApi.get('/test/customers', (db, request) => {
  return {
    success: true,
    data: {
      count: 10,
      items: db.getCollection('customers').data
    }
  }
})
