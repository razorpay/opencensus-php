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
    let { data, fetchFn, filters, items } = props;
    Object.assign(this, { data, fetchFn });

    this.filters = filters
      ? observable.shallowObject(Object.assign({}, defaultFilters, filters))
      : {};

    // load initial values
    // fetch if not pre-populated
    this.items = observable.shallowArray(items || []);
    if (!items) {
      this.fetch();
    }
  }

  fetch() {
    return this.request(
      'fetch',
      this.fetchFn({
        data: this.data,
        queryParams: this.filters,
      })
    ).then(data => {
      if (data) {
        this.items.replace(data.items);
      }
      return data;
    });
  }
}
