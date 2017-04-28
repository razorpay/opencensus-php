import Base from './Base';
import ajax from 'merchant/utils/ajax';
import store from 'merchant/store';

/*
  Abstrace class for most CRUD entities. The base Entity has methods like
  - Class.fetchAll(params)
  - Class.fetch(params)
  - instance.save()
  - instance.delete()
*/

export default class Entity extends Base {
  /*
    `fetchAll` returns a collection of the instances of the resource. This is a static method & should be invoked as [Class].fetchAll(params).
  */
  fetchAll(params = {}) {
    const Klass = this.constructor;
    let { id, ...data } = params;

    if (id) {
      return this.fetch(id, data).then(response => {
        return {
          data: {
            items: [response],
          },
        };
      });
    }

    return ajax(this.resourceUrl, { data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Klass().deserialize(item)
      );
      return response;
    });
  }

  fetch(id, data = {}) {
    const Klass = this.constructor;
    return ajax(`${this.resourceUrl}/${id}`, { data }).then(response => {
      return new Klass().deserialize(response.data.items[0]);
    });
  }

  save() {
    const Klass = this.constructor;
    let params = this.serialize();
    let { id, ...data } = params;
    let [url, method] = this.getResourceUrlAndMethod();

    return ajax({ url, method, data }).then(response => {
      return new Klass().deserialize(response.data);
    });
  }

  delete() {
    return ajax({
      url: this.getResourceUrl(),
      method: 'delete',
    });
  }

  getSession() {
    return store.getState().session;
  }
}
