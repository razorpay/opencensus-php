import DFaqApi from 'dfaqapi'
import customer from './customers'
import plan from './plans'
import subscription from './subscriptions'

let fakeApi = new DFaqApi({
  factories: {
    plan,
    customer,
    subscription
  }
})


fakeApi.createList('customer', 10)
fakeApi.createList('plan', 5)
fakeApi.createList('subscription', 3)

fakeApi.get('/test/subscriptions', (db, request) => {
  return {
    success: true,
    data: {
      count: 10,
      items: db.getCollection('subscription').data
    }
  }
})

fakeApi.get('/test/customers', (db, request) => {
  return {
    success: true,
    data: {
      count: 10,
      items: db.getCollection('customer').data
    }
  }
})

fakeApi.get('/test/plans', (db, request) => {
  return {
    success: true,
    data: {
      count: 10,
      items: db.getCollection('plan').data
    }
  }
})
