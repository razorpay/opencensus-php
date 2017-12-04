import { observable, observe } from 'mobx';
import BaseModel from './base';
import CollectionItem from './collectionItem';
import { notifySuccess, notifyError } from 'common/modal';
import { adminDelete } from 'util/fetch';

const defaultFilters = {
  count: 20,
  skip: 0,
};

export default class Collection extends BaseModel {
  animateItems = true;

  setFilters(filters, noPagination) {
    let newFilters;

    if (noPagination) {
      newFilters = Object.assign({}, filters);
    } else {
      newFilters = Object.assign({}, defaultFilters, filters);
    }

    this.filters = observable.shallowObject(newFilters);
  }
  applyFilters(filters) {
    this.setFilters(filters);
    return this.fetch();
  }

  addFilters(filters) {
    for (let f in defaultFilters) {
      if (f in filters) {
        filters[f] = Number(filters[f]);
      }
    }

    // Clear the empty values. Send value = null, in case you want to clear out the value from the final filters
    for (let key in filters) {
      if (filters[key] == null) {
        delete filters[key];

        if (typeof this.filters[key] !== 'undefined') {
          delete this.filters[key];
        }
      }
    }

    Object.assign(this.filters, defaultFilters, filters);
    return this.fetch();
  }

  constructor(props) {
    super(props);
    let {
      data,
      fetchFn,
      filters,
      items,
      model,
      noPagination,
      deleteRouteName,
    } = props;
    Object.assign(this, { data, fetchFn, model });

    this.deleteRouteName = deleteRouteName;

    this.setFilters(filters, noPagination);

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

  delete = item => {
    const data = {
      route_name: this.deleteRouteName,
      url_params: {
        id: item.id,
      },
    };

    return adminDelete(data)
      .then(data => {
        if (data) {
          this.remove(item);
          notifySuccess('Workflow deleted successfully');
        }
      })
      .catch(err => notifyError(err));
  };

  push(item) {
    this.items.push(new (this.model || CollectionItem)(this, item));
  }

  remove(item) {
    return this.items.remove(item);
  }
}
