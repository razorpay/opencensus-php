import { observable } from 'mobx';
import { notifyError } from 'common/modal';

const defaultFilters = {
  count: 20,
  skip: 0,
};

export default class Collection {
  @observable items = [];

  constructor({ fetchRoute, fetchFn, filters }) {
    this.fetchRoute = fetchRoute;
    this.fetchFn = fetchFn;

    this.pending = observable.box();
    this.filters = observable.box(Object.assign(defaultFilters, filters));

    this.fetch();
  }

  fetch() {
    return this.request('fetch', this.get(this.fetchRoute, this.filters));
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

  get(route, queryParams = {}) {
    return this.fetchFn({
      route,
      queryParams,
    });
  }
}
