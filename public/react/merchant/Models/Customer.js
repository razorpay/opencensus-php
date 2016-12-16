import BaseModel from './Base'
import ajax from 'merchant/utils/ajax'

export default class Customer extends BaseModel {
  resourceIdField = 'id'
  resourceUrl = '/customers'
  resourceProperties = [
    'id',
    'name',
    'email',
    'contact'
  ]

  get displayName() {
    if (this.get('name')) {
      return this.get('name')
    }

    if (this.get('contact')) {
      return `${this.get('contact')} ${this.get('email') ? `(${this.get('email')})` : ''}`
    }

    return this.get('email')
  }

  static fetchAll(params = {}) {
    return ajax('/customers', params).then((response) => {
      response.data.items = response.data.items.map((item) => new Customer().deserialize(item))
      return response
    })
  }

  save() {
    let params = this.serialize()
    let { id, ...data } = params
    let [ url, method ] = this.getResourceUrlAndMethod()

    return ajax({ url, method, data }).then((response) => {
      return new Customer().deserialize(response.data)
    })
  }

  delete() {
    return ajax({
      url: this.getResourceUrl(),
      method: 'delete'
    })
  }
}
