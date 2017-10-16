import { observable, extendShallowObservable } from 'mobx';
import BaseModel from 'model/base';

export default class Item extends BaseModel {
  constructor(collection, props = {}) {
    super();

    extendShallowObservable(this, props);

    this.define('collection', collection);
    this.bind(['onPropChange']);
  }

  onPropChange(e) {
    this[e.target.name] = e.target.value;
  }
}
