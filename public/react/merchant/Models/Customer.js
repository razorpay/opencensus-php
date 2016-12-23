import BaseModel from './Base'
import ajax from 'merchant/utils/ajax'
import { isBlank } from 'rzp/utils/rzp-utils'

export default class Customer extends BaseModel {
  resourceIdField = 'id'
  resourceUrl = '/customers'
  resourceProperties = [
    'id',
    'name',
    'email',
    'contact'
  ]

  static fetchAll(data = {}) {
    return ajax('/customers', { data }).then((response) => {
      response.data.items = response.data.items.map((item) => new Customer().deserialize(item))
      return response
    })
  }

  // This will be replaced with the ES autocomplete api
  static fetchForAutocomplete(data = {}) {
    return ajax('/customers/autocomplete', { data }).then((response) => {
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
    let displayParts = [
      this.name,
      this.contact,
      this.email
    ].filter((item) => !isBlank(item))

    let displayName = `${displayParts.join(' / ').replace('\/ ', '(')}${displayParts.length > 1 ? ')' : ''}`
    this.displayName = displayName
  }
}
