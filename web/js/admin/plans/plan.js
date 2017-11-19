import { toJS, extendObservable, computed } from 'mobx';
import Collection from 'model/collection';
import CollectionItem from 'model/collectionItem';
import { adminDelete, adminFetch, adminPost } from 'util/fetch';
import { methods } from 'util/data';
import { notifySuccess, notifyError } from 'common/modal';
import { deepClone } from 'util/index';
import { cardTypes } from 'util/data';
import { Switch } from 'ui/Field';

export default class Plan extends Collection {
  constructor(props = {}) {
    super({
      items: props.plan_id ? null : [],
      itemsKey: 'rules',
      data: {
        route_name: 'pricing_get_plan',
        url_params: {
          id: props.plan_id,
        },
      },
      model: Rule,
    });
    this.props = props;

    if (!props.plan_id) {
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
      notifyError('Name the pricing plan first.');
      return Promise.resolve();
    }
    return this.request(
      'save',
      adminPost({
        route_name: 'pricing_create_plan',
        body: {
          plan_name: name,
          rules: this.items.slice(0, -1).map(p => p.serialize()),
        },
      }).then(data => {
        data = {
          id: '8tNZHh6z4F47Jf',
          name: 'lolplan',
          entity: 'pricing',
          count: 1,
          rules: [
            {
              id: '8tNZHh8CLUk6dS',
              plan_id: '8tNZHh6z4F47Jf',
              plan_name: 'lolplan',
              feature: 'payment',
              gateway: null,
              payment_method: 'card',
              payment_method_type: null,
              payment_network: null,
              payment_issuer: null,
              emi_duration: null,
              international: false,
              amount_range_active: false,
              amount_range_min: null,
              amount_range_max: null,
              percent_rate: 200,
              fixed_rate: 0,
              min_fee: 0,
              max_fee: null,
              created_at: 1508922799,
              updated_at: 1508922799,
              deleted_at: null,
              expired_at: null,
            },
          ],
        };
        if (data) {
          this.props.collection.items.push(data);
          return data;
        }
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
    ...cardTypes,
  },
  payment_network: {
    '': 'All',
  },
  payment_issuer: {
    '': 'All',
    HDFC: 'HDFC',
    ICIC: 'ICICI',
  },
  international: {
    0: 'No',
    1: 'Yes',
  },
  amount_range: {
    '': 'None',
    '0-100000': '0-100000',
    '100000-200000': '100000-200000',
    '0-200000': '0-200000',
    '200000-1000000000': '200000-1000000000',
  },
  emi_duration: {
    '': 'All',
    ' 3': 3,
    ' 6': 6,
    ' 9': 9,
    ' 12': 12,
    ' 15': 15,
    ' 18': 18,
    ' 21': 21,
    ' 24': 24,
  },
  percent_rate: '',
  fixed_rate: '',
  min_fee: '',
  max_fee: '',
};

const ruleProps = Object.keys(options).reduce(function(o, key) {
  o[key] = options[key];
  if (typeof o[key] === 'object') {
    o[key] = Object.keys(options[key])[0];
  }
  return o;
}, {});

class Rule extends CollectionItem {
  @computed
  get isCard() {
    return /card|emi/.test(this.payment_method);
  }

  constructor(collection, props = ruleProps) {
    super(collection, props);
    this.bind(['save', 'delete']);
    extendObservable(this, props);
  }

  serialize() {
    let data = toJS(this);

    if (data.amount_range) {
      let range = data.amount_range.split('-');
      data.amount_range_active = 1;
      data.amount_range_min = range[0];
      data.amount_range_max = range[1];
    }
    delete data.amount_range;

    data.emi_duration = data.emi_duration.trim();

    data.percent_rate = Math.round(data.percent_rate * 100);
    data.fixed_rate = Math.round(data.fixed_rate * 100);
    data.min_fee = Math.round(data.min_fee * 100);
    data.max_fee = Math.round(data.max_fee * 100);
    return data;
  }

  save() {
    // if unsaved plan
    if (!this.collection.props.plan_id) {
      this.define('readonly', true);
      this.collection.items.push(new Rule(this.collection));
    } else {
      return this.request(
        'save',
        adminPost({
          body: this.serialize(),
          route_name: 'pricing_add_plan_rule',
          url_params: {
            id: this.collection.props.plan_id,
          },
        })
      ).then(data => {
        if (data) {
          notifySuccess(`Rule added for ${data.plan_name}`);
          this.collection.items.splice(-1, 0, new Rule(this.collection, data));
          return data;
        }
      });
    }
  }

  delete() {
    if (!this.collection.props.plan_id) {
      return this.collection.items.remove(this);
    }
    return this.request(
      'delete',
      adminDelete({
        route_name: 'pricing_delete_plan_rule',
        url_params: {
          planId: this.collection.props.plan_id,
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

  readonlyValue(name) {
    var value = this[name] || '';

    if (this.id || this.readonly) {
      return values[value];
    }
    return false;
  }

  field(Component, name, props = {}) {
    var value = this[name] || '';

    if (this.id || this.readonly) {
      if (props.type === 'number') {
        value /= 100;
      }
      return value;
    }

    return (
      <Component
        name={name}
        value={value}
        onChange={this.onPropChange}
        {...props}
      />
    );
  }

  selectField(name, values = options[name]) {
    return this.field('select', name, {
      children: Object.keys(values).map(value => (
        <option value={value} key={value}>
          {values[value]}
        </option>
      )),
    });
  }

  numberField(name, props = {}) {
    return this.field('input', name, {
      type: 'number',
      min: 0,
      step: 0.01,
      ...props,
    });
  }

  binaryField(name) {
    return this.field(Switch, name);
  }

  paymentMethodTypeField() {
    if (this.payment_method === 'card') {
      var field = this.selectField('payment_method_type');
      if (field) {
        return <div>Type {field}</div>;
      }
    }
  }

  internationalField() {
    if (this.payment_method === 'card') {
      var field = this.binaryField('international');
      if (field) {
        return <div>International {field}</div>;
      }
    }
  }

  emiDurationField() {
    if (this.payment_method === 'emi') {
      var field = this.selectField('emi_duration');
      if (field) {
        return <div>{field} Months</div>;
      }
    }
  }
}
