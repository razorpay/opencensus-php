import { observable } from 'mobx';
import BaseModel from './base';

const defaultFilters = {
  count: 20,
  skip: 0,
};

export default class Collection extends BaseModel {
  setFilters(filters) {
    Object.assign(this.filters, filters);
    return this.fetch();
  }

  constructor(props) {
    super(props);
    let { data, fetchFn, filters, items, model } = props;
    Object.assign(this, { data, fetchFn, model });

    this.filters =
      filters === null
        ? {}
        : observable.shallowObject(Object.assign({}, defaultFilters, filters));

    // load initial values
    // fetch if not pre-populated
    this.items = observable.shallowArray(items || []);
    if (!items) {
      this.fetch();
    }
  }

  fetch() {
    return this.request(
      this.fetchFn({
        data: this.data,
        queryParams: this.filters,
      }),
      'fetch'
    ).then(data => {
      if (data) {
        let Model = this.model;
        if (Model) {
          data.items = data.items.map(i => new Model(this, i));
        }
        this.items.replace(data.items);
      }
      return data;
    });
  }
}
