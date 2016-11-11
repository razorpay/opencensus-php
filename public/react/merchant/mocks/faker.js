import DFaqApi from 'dfaqapi'
import Customer from './customers'
import Plan from './plans'
import Subscription from './subscriptions'

let fakeApi = new DFaqApi({
  factories: {
    plan: Plan,
    customer: Customer,
    subscription: Subscription
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

fakeApi.post('/test/customer', (db, request) => {
  let newCustomer = new Customer()
  let customerDB = db.getCollection('customer')

  Object.assign(newCustomer, request.parsedRequestBody)
  customerDB.insert(newCustomer)
  return {
    success: true,
    data: {
      customer: newCustomer
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
