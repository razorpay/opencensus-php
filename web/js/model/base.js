import { observable, extendObservable } from 'mobx';
import { notifyError } from 'common/modal';

export default class BaseModel {
  constructor() {
    this.define('pending', observable({}));
  }

  request(name, promise) {
    if (typeof name === 'string') {
      extendObservable(this.pending, {
        [name]: true,
      });
    } else {
      promise = name;
    }

    return promise.then(data => {
      if (name) {
        this.pending[name] = false;
      }
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
