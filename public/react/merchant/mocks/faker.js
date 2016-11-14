import DFaqApi from 'dfaqapi'
import Customer from './customers'
import Plan from './plans'
import Subscription from './subscriptions'
import Invoice from './invoices'

let fakeApi = new DFaqApi({
  factories: {
    plan: Plan,
    customer: Customer,
    subscription: Subscription,
    invoice: Invoice
  }
})

fakeApi.createList('customer', 10)
fakeApi.createList('plan', 5)
fakeApi.createList('subscription', 3)
fakeApi.createList('invoice', 5)

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

fakeApi.put('/test/customer/:id', (db, request) => {
  let customerDB = db.getCollection('customer')
  let customer = customerDB.findOne({ id: request.params.id })
  let parsedRequestBody = request.parsedRequestBody
  for (let key in customer) {
    if(customer.hasOwnProperty(key) && parsedRequestBody[key]) {
      let value = parsedRequestBody[key]
      if (key === '$loki') {
        value = +value
      }
      customer[key] = value
    }
  }
  customerDB.update(customer)

  return {
    success: true,
    data: {
      customer: customer
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

fakeApi.post('/test/plan', (db, request) => {
  let newPlan = new Plan()
  let planDB = db.getCollection('plan')

  Object.assign(newPlan, request.parsedRequestBody)
  planDB.insert(newPlan)
  return {
    success: true,
    data: {
      plan: newPlan
    }
  }
})

fakeApi.put('/test/plan/:id', (db, request) => {
  let planDB = db.getCollection('plan')
  let plan = planDB.findOne({ id: request.params.id })
  let parsedRequestBody = request.parsedRequestBody
  for (let key in plan) {
    if(plan.hasOwnProperty(key) && parsedRequestBody[key]) {
      let value = parsedRequestBody[key]
      if (key === '$loki') {
        value = +value
      }
      plan[key] = value
    }
  }
  planDB.update(plan)

  return {
    success: true,
    data: {
      plan: plan
    }
  }
})

fakeApi.get('/test/invoices', (db, request) => {
  return {
    success: true,
    data: {
      count: 10,
      items: db.getCollection('invoice').data
    }
  }
})
