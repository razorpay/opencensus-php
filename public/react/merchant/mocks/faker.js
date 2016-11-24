import DFaqApi from 'dfaqapi'
import Customer from './customer'
import Plan from './plan'
import Item from './item'
import Subscription from './subscription'
import Invoice from './invoice'

let fakeApi = new DFaqApi({
  factories: {
    plan: Plan,
    customer: Customer,
    subscription: Subscription,
    invoice: Invoice,
    item: Item
  }
})

fakeApi.createList('customer', 10)
fakeApi.createList('item', 20)
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

fakeApi.get('/test/items', (db, request) => {
  return {
    success: true,
    data: {
      count: 10,
      items: db.getCollection('item').data
    }
  }
})

fakeApi.post('/test/item', (db, request) => {
  let newItem = new Item()
  let itemDB = db.getCollection('item')

  Object.assign(newItem, request.parsedRequestBody)
  itemDB.insert(newItem)
  return {
    success: true,
    data: {
      item: newItem
    }
  }
})

fakeApi.put('/test/item/:id', (db, request) => {
  let itemDB = db.getCollection('item')
  let item = itemDB.findOne({ id: request.params.id })
  let parsedRequestBody = request.parsedRequestBody
  for (let key in item) {
    if(item.hasOwnProperty(key) && parsedRequestBody[key]) {
      let value = parsedRequestBody[key]
      if (key === '$loki') {
        value = +value
      }
      item[key] = value
    }
  }
  itemDB.update(item)

  return {
    success: true,
    data: {
      item
    }
  }
})


// fakeApi.get('/test/invoices', (db, request) => {
//   return {
//     success: true,
//     data: {
//       count: 10,
//       items: db.getCollection('invoice').data
//     }
//   }
// })
