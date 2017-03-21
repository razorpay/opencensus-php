import Entity from './Entity'
import ajax from 'merchant/utils/ajax'
import { getFixedINRAmount } from 'rzp/utils/rzp-utils'

export default class Item extends Entity {
  resourceUrl = '/items'
  resourceFields = [
    'id',
    'name',
    'amount',
    'currency',
    'description'
  ]
  currency = 'INR'

  // This will be replaced with the ES autocomplete api
  fetchForAutocomplete(data = {}) {
    return ajax('/items/autocomplete', { data }).then((response) => {
      response.data.items = response.data.items.map((item) => new Item().deserialize(item))
      return response
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
