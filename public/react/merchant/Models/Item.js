import BaseModel from './Base'
import ajax from 'merchant/utils/ajax'
import { getFixedINRAmount } from 'rzp/utils/rzp-utils'

export default class Item extends BaseModel {
  resourceIdField = 'id'
  resourceUrl = '/items'
  resourceProperties = [
    'id',
    'name',
    'amount',
    'currency',
    'description'
  ]
  currency = 'INR'

  static fetchAll(data = {}) {
    return ajax('/items', { data }).then((response) => {
      response.data.items = response.data.items.map((item) => new Item().deserialize(item))
      return response
    })
  }

  // This will be replaced with the ES autocomplete api
  static fetchForAutocomplete(data = {}) {
    return ajax('/items/autocomplete', { data }).then((response) => {
      response.data.items = response.data.items.map((item) => new Item().deserialize(item))
      return response
    })
  }

  save() {
    let params = this.serialize()
    let { id, ...data } = params
    let [ url, method ] = this.getResourceUrlAndMethod()

    return ajax({ url, method, data }).then((response) => {
      return new Item().deserialize(response.data)
    })
  }

  delete() {
    return ajax({
      url: this.getResourceUrl(),
      method: 'delete'
    })
  }

  serializeProperty(prop) {
    if (prop === 'amount') {
      return Number(this.amountInINR) * 100
    }
    return super.serializeProperty(prop)
  }

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = getFixedINRAmount(value)
    }
    return super.deserializeProperty(prop, value)
  }
}
