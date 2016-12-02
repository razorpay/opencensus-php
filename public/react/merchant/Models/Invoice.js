import BaseModel from './Base'
import ajax from 'merchant/utils/ajax'
import { getFixedINRAmount } from 'rzp/utils/rzp-utils'

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
    'description',
    'receipt'
  ]
  currency = 'INR'

  static fetchAll(params = {}) {
    return ajax('/invoices', params).then((response) => {
      response.data.items = response.data.items.map((item) => new Invoice().deserialize(item))
      return response
    })
  }

  static fetch(id, params = {}) {
    return ajax(`/invoices/${id}`, params).then((response) => {
      return new Invoice().deserialize(response.data.items[0])
    })
  }

  save() {
    let params = this.serialize()
    let { id, ...data } = params
    let [ url, method ] = this.getResourceUrlAndMethod()

    return ajax({ url, method, data }).then((response) => {
      return new Invoice().deserialize(response.data)
    })
  }

  notify(type) {
    return ajax({
      url: `${this.getResourceUrl()}/notify/${type}`,
      method: 'post'
    })
  }

  serializeProperty(prop) {
    if (prop === 'sms_notify' || prop === 'email_notify') {
      return this[prop] ? 1 : 0
    }

    if (prop === 'line_items') {
      if (this.type === 'link') {
        return this.line_items.map((item) => {
          return {
            name: item.name,
            amount: Number(item.amount) * 100
          }
        })
      } else if (this.type === 'invoice') {
        return this.line_items.map((item) => {
          return {
            item_id: item.item_id,
            quantity: item.quantity
          }
        })
      }
    }

    return super.serializeProperty(prop)
  }

  deserializeProperty(prop, value) {
    if (prop === 'customer_details') {
      this.customer = {
        name: value.customer_name,
        email: value.customer_email,
        contact: value.customer_contact,
        address: value.customer_address
      }
    }

    if (prop === 'line_items') {
      value = value.map((item) => {
        item.amountInINR = getFixedINRAmount(item.amount)
        return item
      })
    }

    return super.deserializeProperty(prop, value)
  }
}
