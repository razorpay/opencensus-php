import BaseModel from './Base'
import ajax from 'merchant/utils/ajax'

export default class Invoice extends BaseModel {
  resourceIdField = 'id'
  resourceUrl = '/invoices'
  resourceProperties = [
    'id',
    'amount',
    'currency',
    'date',
    'customer_id',
    'customer',
    'sms_notify',
    'email_notify',
    'line_items',
    'type',
    'terms',
    'description'
  ]
  currency = 'INR'
  date = Math.ceil(new Date().getTime()/1000)

  static fetchAll(params = {}) {
    return ajax('/invoices', params).then((response) => {
      response.data.items = response.data.items.map((item) => new Invoice().deserialize(item))
      return response
    })
  }

  static fetch(id, params = {}) {
    return ajax(`/invoices/${id}`, params).then((response) => {
      return new Invoice().deserialize(response.data)
    })
  }

  save() {
    let params = this.serialize()
    let { id, ...data } = params
    let [ url, method ] = this.getResourceUrlAndMethod()

    return ajax({
      url,
      method,
      data
    }).then((response) => {
      return new Invoice().deserialize(response.data)
    })
  }

  serializeProperty(prop) {
    if (prop === 'sms_notify' || prop === 'email_notify') {
      return this[prop] ? 1 : 0
    }

    if (prop === 'line_items') {
      return this.line_items.map((item) => {
        return {
          name: item.name,
          amount: Number(item.amount) * 100
        }
      })
    }

    return super.serializeProperty(prop)
  }
}
