import Base from './Base'
import ajax from 'merchant/utils/ajax'

export default class Entity extends Base {
  static fetchAll(params = {}) {
    const Klass = this
    let { id, ...data } = params

    if (id) {
      return Klass.fetch(id, data).then((response) => {
        return {
          data: {
            items: [response]
          }
        }
      })
    }

    return ajax(Klass.resourceUrl, { data }).then((response) => {
      response.data.items = response.data.items.map((item) => new Klass().deserialize(item))
      return response
    })
  }

  static fetch(id, data = {}) {
    const Klass = this
    return ajax(`${Klass.resourceUrl}/${id}`, { data }).then((response) => {
      return new Klass().deserialize(response.data.items[0])
    })
  }

  save() {
    const Klass = this.constructor
    let params = this.serialize()
    let { id, ...data } = params
    let [ url, method ] = this.getResourceUrlAndMethod()

    return ajax({ url, method, data }).then((response) => {
      return new Klass().deserialize(response.data)
    })
  }

  delete() {
    return ajax({
      url: this.getResourceUrl(),
      method: 'delete'
    })
  }
}
