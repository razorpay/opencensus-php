import { observable, extendObservable } from 'mobx';
import Collection from 'model/collection';
import CollectionItem from 'model/collectionItem';
import { adminDelete, adminFetch, adminPost } from 'util/fetch';
import { methods } from 'util/data';
import { notifySuccess, notifyError } from 'common/modal';
import { deepClone } from 'util/index';

export default class Plan extends Collection {
  constructor(props = {}) {
    super({
      items: props.id ? null : [],
      itemsKey: 'rules',
      data: {
        route_name: 'pricing_get_plan',
        url_params: {
          id: props.id,
        },
      },
      model: Rule,
    });
    this.props = props;

    if (!props.id) {
      this.items.push(new Rule(this));
    }

    this.bind(['save', 'updateName']);
  }

  fetch() {
    return this.request(
      'fetch',
      adminFetch({
        ...this.data,
        query_params: this.filters,
      })
    ).then(data => {
      this.items.replace(
        (data.rules || []).map(p => new Rule(this, p)).concat(new Rule(this))
      );
      return data;
    });
  }

  save() {
    let name = this.props.name;
    if (!name) {
      return notifyError('Name the pricing plan first.');
    }
    return this.request(
      'save',
      adminPost({
        route_name: 'pricing_create_plan',
        plan_name: name,
        rules: this.items.slice(1).map(p => p.props),
      })
    );
  }

  updateName(e) {
    this.props.name = e.target.value;
  }
}

export const options = {
  feature: {
    payment: 'Payment',
    recurring: 'Recurring',
    payout: 'Payout',
    transfer: 'Transfer',
    emi: 'EMI',
  },
  payment_method: {
    ...methods,
    transfer: 'Transfer',
    bank_transfer: 'Bank Transfer',
    fund_transfer: 'Payout: Fund Transfer',
    account: 'Transfer: Account (Marketplace)',
    customer: 'Transfer: Customer (Openwallet)',
  },
  payment_method_type: {
    '': 'All',
    credit: 'Credit',
    debit: 'Debit',
  },
};

const ruleProps = Object.keys(options).reduce(function(o, key) {
  o[key] = options[key] && Object.keys(options[key])[0];
  return o;
}, {});

class Rule extends CollectionItem {
  constructor(collection, props = ruleProps) {
    super(collection, props);
    this.bind(['save', 'delete']);
    extendObservable(this, props);
  }

  save() {
    // if unsaved plan
    if (!this.collection.props.id) {
      this.define('readonly', true);
      let copy = this.copy;
      copy.readonly = true;
      return this.collection.items.push(copy);
    }
    return this.request(
      'save',
      adminPost({
        body: this,
        route_name: 'pricing_add_plan_rule',
        url_params: {
          id: this.collection.props.id,
        },
      })
    ).then(data => {
      if (data) {
        notifySuccess(`Rule added for ${data.plan_name}`);
        this.collection.items.splice(-1, 0, Object.assign(this.copy, data));
      }
    });
  }

  delete() {
    if (!this.collection.props.id) {
      return this.collection.items.remove(this);
    }
    return this.request(
      'delete',
      adminDelete({
        route_name: 'pricing_delete_plan_rule',
        url_params: {
          planId: this.collection.props.id,
          ruleId: this.id,
        },
      })
    ).then(data => {
      if (data) {
        notifySuccess(data.message);
        this.collection.items.remove(this);
      }
    });
  }
}
