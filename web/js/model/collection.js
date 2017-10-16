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
      data = {
        success: true,
        data: {
          entity: 'collection',
          count: 1,
          admin: true,
          items: [
            {
              id: '8O9FFfrUed72LQ',
              merchant_id: '100000Razorpay',
              gateway: 'hdfc',
              type: 'sorter',
              group: null,
              filter_type: null,
              load: 25,
              gateway_acquirer: null,
              network_category: null,
              shared_terminal: null,
              international: null,
              network: null,
              method: 'card',
              method_type: null,
              issuer: null,
              min_amount: 0,
              max_amount: null,
              iins: [],
              currency: 'INR',
              emi_duration: null,
              emi_subvention: null,
              category2: null,
              created_at: 1502103815,
              updated_at: 1502103815,
              deleted_at: null,
              entity: 'gateway_rule',
              admin: true,
            },
          ],
        },
      };
      data = data.data;
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
