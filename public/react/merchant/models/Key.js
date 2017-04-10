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
    return this.isNew ? 'post' : 'put'
  }

  resourceFields() {
    return this.isNew ? [] : rollKeyFields
  }
}
