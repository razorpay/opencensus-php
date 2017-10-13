import { observable } from 'mobx';
import { notifyError } from 'common/modal';

const defaultFilters = {
  count: 20,
  skip: 0,
};

export default class Collection {
  setFilters(filters) {
    Object.assign(this.filters, filters);
    return this.fetch();
  }

  constructor({ data, fetchFn, filters, items }) {
    this.fetchFn = fetchFn;
    this.data = data;
    this.pending = observable.box();

    if (filters !== null) {
      this.filters = observable.shallowObject(
        Object.assign({}, defaultFilters, filters)
      );
    }

    // load initial values
    // fetch if not pre-populated
    this.items = observable.shallowArray(items || []);
    if (!items) {
      this.fetch();
    }
  }

  fetch() {
    this.pending.set(true);

    return this.fetchFn({
      data: this.data,
      queryParams: this.filters,
    })
      .then(({ data }) => {
        if (!data.success) {
          throw data.errors[0];
        }
        this.items.replace(data.data.items);
      })
      .catch(e => notifyError(e))
      .then(_ => {
        this.pending.set(false);
      });
  }
}
