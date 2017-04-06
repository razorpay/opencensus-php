import GenericEntity from './GenericEntity'
import ajax from 'merchant/utils/ajax'
import { getFixedINRAmount, isBlank } from 'rzp/utils/rzp-utils'

const rollKeyFields = [
  'id',
  'delay_roll'
]

export default class Key extends GenericEntity {
  listRouteName = 'merchant_fetch_keys'

  fetchAll(params = {}) {
    const Klass = this.constructor
    let id = params.id

    let data = {
      url_params: {
        '{id}': id
      }
    }

    data.route_name = this.listRouteName
    return this.makeGenericAjaxCall({ data }).then((response) => {
      response.data.items = response.data.items.map(
        (item) => new Klass().deserialize(item)
      )
      return response
    })
  }

  save() {
    const Klass = this.constructor
    let self = this
    let params = this.serialize()
    let url = this.resourceUrl
    let method = this.getResourceMethod()

    let data = {
      route_name: this.getRouteName()
    }
    if (this.isNew) {
      data.url_params = JSON.stringify({
        '{id}': this.merchantId
      })
    } else {
      data.url_params = JSON.stringify({
        '{keyId}': params.id,
        '{merchantId}': this.merchantId,
      })
      data.body = params
    }

    return this.makeGenericAjaxCall({
      method,
      data,
    }).then((response) => {
      if (self.isNew) {
        return new Klass().deserialize(response.data)
      } else {
        return {
          'new': new Klass().deserialize(response.data.new),
          'old': new Klass().deserialize(response.data.old),
          'removeOld':  1 - params.delay_roll
        }
      }
    })
  }


  getRouteName() {
    return this.isNew ? 'merchant_create_key' : 'merchant_replace_key'
  }

  getResourceMethod() {
    return this.isNew ? 'post' : 'put';
  }


  resourceFields() {
    return this.isNew ? [] : rollKeyFields
  }

  notify(type) {
    return ajax({
      url: `/invoices/${this.id}/notify/${type}`,
      method: 'post',
    })
  }

  cancel() {
    return this.makeGenericAjaxCall({
      method: 'post',
      data: {
        route_name: 'invoice_cancel',
        url_params: JSON.stringify({
          '{id}': this.id,
        }),
      }
    }).then((response) => {
      return new Invoice().deserialize(response.data)
    })
  }

  serializeProperty(prop) {
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
