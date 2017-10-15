import { observable, extendObservable } from 'mobx';
import Collection from 'model/collection';
import BaseModel from 'model/base';
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
        data: this.data,
        queryParams: this.filters,
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
        data: {
          route_name: 'pricing_create_plan',
          body: {
            plan_name: name,
            rules: this.items.slice(1).map(p => p.props),
          },
        },
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

class Rule extends BaseModel {
  constructor(plan, props = ruleProps) {
    super();
    this.plan = plan;

    if (!props.id) {
      props = Object.assign(props, ruleProps);
    }
    this.bind(['onPropChange', 'save', 'delete']);
    this.props = observable(props);
  }

  onPropChange(e) {
    this.props[e.target.name] = e.target.value;
  }

  save() {
    // if unsaved plan
    if (!this.plan.props.id) {
      this.readonly = true;
      return this.plan.items.push(new Rule(this.plan, deepClone(this.props)));
    }
    return this.request(
      'save',
      adminPost({
        data: {
          route_name: 'pricing_add_plan_rule',
          url_params: {
            id: this.plan.props.id,
          },
          body: this.props,
        },
      })
    ).then(data => {
      if (data) {
        notifySuccess(`Rule added for ${data.plan_name}`);
        this.plan.items.splice(-1, 0, new Rule(this.plan, data));
      }
    });
  }

  delete() {
    if (!this.plan.props.id) {
      return this.plan.items.remove(this);
    }
    return this.request(
      'delete',
      adminDelete({
        data: {
          route_name: 'pricing_delete_plan_rule',
          url_params: {
            planId: this.plan.props.id,
            ruleId: this.props.id,
          },
        },
      })
    ).then(data => {
      if (data) {
        notifySuccess(data.message);
        this.plan.items.remove(this);
      }
    });
  }
}
