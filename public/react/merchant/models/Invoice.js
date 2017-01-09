import Entity from './Entity'
import ajax from 'merchant/utils/ajax'
import { getFixedINRAmount, isBlank } from 'rzp/utils/rzp-utils'

const createFields = [
  'id',
  'amount',
  'currency',
  'date',
  'draft',
  'customer_id',
  'customer',
  'sms_notify',
  'email_notify',
  'line_items',
  'type',
  'terms',
  'description',
  'receipt',
  'notes'
]

const editableFieldsInIssuedState = [
  'date',
  'terms',
  'notes',
  'receipt'
]

export default class Invoice extends Entity {
  static resourceIdField = 'id'
  static resourceUrl = '/invoices'
  currency = 'INR'

  resourceFields() {
    return this.status === 'issued' ? editableFieldsInIssuedState : createFields
  }

  get isEditable() {
    return this.status !== 'paid'
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

    if (prop === 'amount' && !isBlank(this.amountInINR)) {
      return Number(this.amountInINR) * 100
    }

    if (prop === 'customer' && this.type === 'invoice' && !this.isNew) {
      return undefined
    }

    if (prop === 'line_items' && !isBlank(this.line_items)) {
      if (this.type === 'link') {
        return this.line_items.map((item) => {
          return {
            name: item.name,
            amount: Number(item.amount) * 100
          }
        })
      } else if (this.type === 'invoice') {
        return this.line_items.map((item, index) => {
          let lineItem = {
            quantity: item.quantity
          }

          if (item.item_id) {
            lineItem.item_id = item.item_id
          } else {
            lineItem.id = this.line_items[index]['id']
          }

          return lineItem
        })
      }
    }

    return super.serializeProperty(prop)
  }

  deserializeProperty(prop, value) {
    switch(prop) {
      case 'customer_details':
        this.customer = {
          name: value.customer_name,
          email: value.customer_email,
          contact: value.customer_contact,
          address: value.customer_address
        }
        break

      case 'amount':
        this.amountInINR = getFixedINRAmount(value)
        break

      case 'line_items':
        value = value.map((item) => {
          item.amountInINR = getFixedINRAmount(item.amount)
          return item
        })
        break

      case 'sms_status':
        this.sms_notify = !isBlank(value)
        break

      case 'email_status':
        this.email_notify = !isBlank(value)
        break
    }

    return super.deserializeProperty(prop, value)
  }
}
