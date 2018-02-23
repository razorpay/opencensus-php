import { observable, observe } from 'mobx';
import BaseModel from './base';
import CollectionItem from './collectionItem';
import { notifySuccess, notifyError } from 'common/modal';
import { adminDelete } from 'common/fetch';

export const defaultFilters = {
  count: 20,
  skip: 0,
};

export default class Collection extends BaseModel {
  animateItems = true;

  setFilters(filters) {
    let newFilters;

    if (this.noPagination) {
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
      extraFields = {},
      noPagination,
      deleteUrl,
    } = props;
    Object.assign(this, { data, fetchFn, model });

    this.deleteUrl = deleteUrl;
    this.noPagination = noPagination;

    this.extraFields = extraFields;

    this.setFilters(filters);

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
    delete this.filters.mode; // mode no longer need to be sent in any request

    return this.request(
      'fetch',
      this.fetchFn({
        ...this.data,
        params: this.filters,
      })
    ).then(data => {
      if (data) {
        let Model = this.model || CollectionItem;

        const items = data.items ? data.items : data; // pricing_get_merchant_plans api no longer has data.items

        // If searching for particular id
        if (items instanceof Array) {
          data.items = items.map(i => new Model(this, i));
        } else {
          data.items = [new Model(this, items)];
        }
        this.items.replace(data.items);
        this.animateItems = false;
      }
      return data;
    });
  }

  delete = item => {
    if (typeof this.deleteUrl === 'function') {
      const data = {
        url: this.deleteUrl(item.id),
      };

      return adminDelete(data)
        .then(data => {
          if (data) {
            this.remove(item);
            notifySuccess('Workflow deleted successfully');
          }
        })
        .catch(err => notifyError(err));
    } else {
      notifyError("entity doesn't have delete url");
    }
  };

  push(item) {
    this.items.push(new (this.model || CollectionItem)(this, item));
  }

  remove(item) {
    return this.items.remove(item);
  }
}
