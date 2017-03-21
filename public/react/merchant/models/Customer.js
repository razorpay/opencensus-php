import Entity from './Entity'
import ajax from 'merchant/utils/ajax'
import { isBlank } from 'rzp/utils/rzp-utils'

export default class Customer extends Entity {
  resourceUrl = '/customers'

  resourceFields = [
    'id',
    'name',
    'email',
    'contact'
  ]

  getResourceMethod() {
    return this.isNew ? 'post' : 'put'
  }

  // This will be replaced with the ES autocomplete api
  fetchForAutocomplete(data = {}) {
    return ajax('/customers/autocomplete', { data }).then((response) => {
      response.data.items = response.data.items.map((item) => new Customer().deserialize(item))
      return response
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
