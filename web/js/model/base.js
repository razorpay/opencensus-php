import { observable, extendObservable } from 'mobx';
import { notifyError } from 'common/modal';

export default class BaseModel {
  constructor() {
    this.define('pending', observable({}));
  }

  request(name, promise) {
    extendObservable(this.pending, {
      [name]: true,
    });

    return promise
      .then(({ data }) => {
        if (!data.success) {
          throw data.errors[0];
        }
        return data.data;
      })
      .catch(e => notifyError(e))
      .then(data => {
        this.pending[name] = false;
        return data;
      });
  }

  bind(methods) {
    methods.forEach(m => this.define(m, this[m].bind(this)));
  }

  define(prop, value) {
    Object.defineProperty(this, prop, {
      value,
      enumerable: false,
      writable: true,
    });
  }
}
