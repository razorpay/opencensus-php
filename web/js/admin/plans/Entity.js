import React, { Component } from 'react';
import { openModal } from 'common/modal';
import Duplexes from 'ui/Duplexes';
import Plan, { options } from './plan';
import { observer } from 'mobx-react';
import * as item from 'ui/Item';
import AsyncButton from 'ui/AsyncButton';
import Field from 'ui/Field';

@observer
export default class PlanEntity extends Component {
  collection = new Plan(this.props.plan);

  render() {
    let { props, items, save, updateName } = this.collection;
    return (
      <div>
        <header>
          {(props.id && props.name) || (
            <div>
              Enter Plan Name: <Field onChange={updateName} />
              {items.length > 1 && (
                <AsyncButton
                  class="btn"
                  pendingClass="btn spinner"
                  onClick={save}
                  text="Save Plan"
                />
              )}
            </div>
          )}
        </header>
        <Duplexes model={this.collection} fields={fields} />
      </div>
    );
  }
}

// options.feature[item.feature]
const namedKey = (item, name) => {
  if (item.props.id || item.readonly) {
    return options[name][item[name] || ''];
  } else {
    return (
      <select name={name} value={item[name]} onChange={item.onPropChange}>
        {Object.keys(options[name]).map(value => (
          <option value={value} key={value}>
            {options[name][value]}
          </option>
        ))}
      </select>
    );
  }
};

const fields = [
  item => item.props.id && ['', item.props.id],
  item => ['Feature', namedKey(item, 'feature')],
  item => item.props.feature && ['Method', namedKey(item, 'payment_method')],
  item =>
    item.props.feature === 'payment' && [
      'Card Type',
      namedKey(item, 'payment_method_type'),
    ],
  item => [
    '',
    ((item.props.id || item.readonly) && (
      <AsyncButton
        class="link danger"
        pendingClass="spinner"
        text="Delete"
        onClick={item.delete}
      />
    )) || (
      <AsyncButton
        class="btn"
        text="Add"
        pendingClass="spinner"
        onClick={item.save}
      />
    ),
  ],
];

export function openPricingEntity() {
  openModal(<PlanEntity plan={this} />);
}
