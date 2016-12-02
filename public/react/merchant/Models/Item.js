import BaseModel from './Base'
import ajax from 'merchant/utils/ajax'

const getFixedINRAmount = (amount) => (Number(amount)/100).toFixed(2)

export default class Item extends BaseModel {
  resourceIdField = 'id'
  resourceUrl = '/item'
  resourceProperties = [
    'id',
    'name',
    'amount',
    'currency',
    'description'
  ]
  currency = 'INR'

  static fetchAll(params = {}) {
    return ajax('/items', params).then((response) => {
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
