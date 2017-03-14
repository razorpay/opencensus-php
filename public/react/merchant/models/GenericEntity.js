import Entity from './Entity'
import ajax from 'merchant/utils/ajax'

// `GenericEntity will replace the `Entity` when all routes are migrated to `/generic` routes

export default class GenericEntity extends Entity {
  static resourceUrl = '/generic'

  static makeGenericAjaxCall(data) {
    return ajax('/generic', {
      data,
      appendModeInQueryParam: true
    })
  }

  static fetchAll(params = {}) {
    const Klass = this
    let { id, ...queryParams } = params
    let data = {
      query_params: JSON.stringify(queryParams)
    }

    if (id) {
      return Klass.fetch(id, data).then((response) => {
        return {
          data: {
            items: [response]
          }
        }
      })
    }

    data.route_name = Klass.listRouteName
    return this.makeGenericAjaxCall(data).then((response) => {
      response.data.items = response.data.items.map((item) => new Klass().deserialize(item))
      return response
    })
  }

  static fetch(id, data = {}) {
    const Klass = this
    data.url_params = JSON.stringify({
      '{id}': id
    })
    data.route_name = Klass.detailsRouteName
    return this.makeGenericAjaxCall(data).then((response) => {
      return new Klass().deserialize(response.data)
    })
  }

  // `makeGenericAjaxCall` is both a static & instance method
  makeGenericAjaxCall(data) {
    const Klass = this.constructor
    return Klass.makeGenericAjaxCall(data)
  }
}
