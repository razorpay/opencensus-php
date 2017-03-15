import Entity from './Entity'
import ajax from 'merchant/utils/ajax'

// `GenericEntity will replace the `Entity` when all routes are migrated to `/generic` routes
export default class GenericEntity extends Entity {
  resourceUrl = '/generic'

  fetchAll(params = {}) {
    const Klass = this.constructor
    let { id, ...queryParams } = params
    let data = {
      query_params: JSON.stringify(queryParams)
    }

    if (id) {
      return this.fetch(id, data).then((response) => {
        return {
          data: {
            items: [response]
          }
        }
      })
    }

    data.route_name = this.listRouteName
    return this.makeGenericAjaxCall(data).then((response) => {
      response.data.items = response.data.items.map((item) => new Klass(item))
      return response
    })
  }

  fetch(id, data = {}) {
    const Klass = this.constructor
    data.url_params = JSON.stringify({
      '{id}': id
    })
    data.route_name = this.detailsRouteName
    return this.makeGenericAjaxCall(data).then((response) => {
      return new Klass(response.data)
    })
  }

  makeGenericAjaxCall(data) {
    return ajax(this.resourceUrl, {
      data,
      appendModeInQueryParam: true
    })
  }
}
