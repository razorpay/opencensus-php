import { observable } from 'mobx';
import { notifyError } from 'common/modal';

const defaultFilters = {
  count: 20,
  skip: 0,
};

export default class Collection {
  constructor({ fetchRoute, fetchFn, filters, items, urlParams }) {
    this.fetchRoute = fetchRoute;
    this.fetchFn = fetchFn;
    this.urlParams = urlParams;

    this.pending = observable.box();
    this.filters = observable.shallowObject(
      Object.assign(defaultFilters, filters)
    );

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
        route: this.fetchRoute,
        queryParams: this.filters,
        urlParams: this.urlParams,
      })
    );
  }

  request(name, promise) {
    this.pending.set(true);

    return promise
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
