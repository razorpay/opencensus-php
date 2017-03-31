import GenericEntity from './GenericEntity'
import ajax from 'merchant/utils/ajax'
import { isBlank } from 'rzp/utils/rzp-utils'

export default class Customer extends GenericEntity {
  listRouteName = 'customer_fetch_multiple'
  deleteRouteName = 'customer_delete'

  resourceFields = [
    'id',
    'name',
    'email',
    'contact'
  ]

  getRouteName() {
    return this.isNew ? 'customer_create' : 'customer_update'
  }

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
