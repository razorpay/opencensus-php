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

  didDeserialize() {
    let email = this.email
    let name = this.name
    let contact = this.contact
    let displayName

    if (name) {
      displayName = name
    } else if (contact) {
      displayName = `${contact} ${email ? `(${email})` : ''}`
    } else {
      displayName = email
    }

    this.displayName = displayName
  }
}
