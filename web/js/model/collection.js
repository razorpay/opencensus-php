import { observable, observe } from 'mobx';
import BaseModel from './base';
import CollectionItem from './collectionItem';

const defaultFilters = {
  count: 20,
  skip: 0,
};

export default class Collection extends BaseModel {
  animateItems = true;

  setFilters(filters) {
    for (let f in defaultFilters) {
      if (f in filters) {
        filters[f] = Number(filters[f]);
      }
    }
    Object.assign(this.filters, { skip: 0 }, filters);
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

    observe(this.items, _ => {
      this.animateItems = true;
    });
  }

  fetch() {
    return this.request(
      'fetch',
      this.fetchFn({
        ...this.data,
        query_params: this.filters,
      })
    ).then(data => {
      if (data) {
        let Model = this.model || CollectionItem;

        const items = data.items ? data.items : data; // pricing_get_merchant_plans api no longer has data.items
        data.items = items.map(i => new Model(this, i));
        this.items.replace(data.items);
        this.animateItems = false;
      }
      return data;
    });
  }

  push(item) {
    this.items.push(new (this.model || CollectionItem)(this, item));
  }

  remove(item) {
    return this.items.remove(item);
  }
}
