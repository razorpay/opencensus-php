import Base from './Base';
import ajax from 'merchant/utils/ajax';

/*
  Abstract class for most CRUD entities. The base Entity has methods like
  - instance.fetchAll(params)
  - instance.fetch(params)
  - instance.save()
  - instance.delete()
*/

export default class Entity extends Base {
  /*
    `fetchAll` returns a collection of the instances of the resource. This is a static method & should be invoked as [Class].fetchAll(params).
  */
  fetchAll(params = {}) {
    const Klass = this.constructor;
    const { id, appendModeInURL, appendModeInQueryParam, ...data } = params;

    if (id) {
      return this.fetch(id, data).then((response) => {
        return {
          data: {
            items: [response],
          },
        };
      });
    }

    return ajax(this.resourceUrl, {
      appendModeInURL,
      appendModeInQueryParam,
      data,
    }).then((response) => {
      response.data.items = response.data.items.map((item) => new Klass(item).deserialize());
      return response;
    });
  }

  fetch(id, data = {}) {
    const Klass = this.constructor;
    return ajax(`${this.resourceUrl}/${id}`, { data }).then((response) => {
      return new Klass(response.data.items[0]).deserialize();
    });
  }

  save() {
    const Klass = this.constructor;
    const params = this.serialize();
    const { id, ...data } = params;
    const [url, method] = this.getResourceUrlAndMethod();

    return ajax({ url, method, data }).then((response) => {
      return new Klass(response.data).deserialize();
    });
  }

  delete() {
    return ajax({
      url: this.getResourceUrl(),
      method: 'delete',
    });
  }
}
