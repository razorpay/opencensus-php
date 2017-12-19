import React, { Component } from 'react';
import Amount from 'ui/Amount';
import { snakeToTitleCase, formatDate } from 'util/index';
import { statusPill, prefixEntityValue } from 'util/data';

const defaultClass = 'table table-striped';

export default class Duplex extends Component {
  render() {
    let { fields, pending, model, mode } = this.props;

    return (
      <div class="duplex">
        {(pending && <div class="table-pending" />) ||
          (model &&
            fields.length && (
              <div class={defaultClass}>
                {fields.map((itemFn, index) => {
                  const result = itemFn(model);
                  if (!result) {
                    return;
                  }
                  let value = getValue(result, mode || model.mode, model);

                  return (
                    result && (
                      <div class="tr" key={index}>
                        <div class="td">{snakeToTitleCase(result[0])}</div>
                        <div class="td text-right">{value}</div>
                      </div>
                    )
                  );
                })}
              </div>
            )) || <div class="table-empty" />}
      </div>
    );
  }
}

function getValue(result, mode, attributes) {
  let [key, value] = result;

  if (!value) {
    // do nothing
  } else if (typeof value === 'boolean') {
    value = (
      <span>
        <i class={`${value ? 'i-yes text-success' : 'i-no text-danger'}`} />
      </span>
    );
  } else if (key.endsWith('status')) {
    value = statusPill(value);
  } else if (
    // charge_at is time
    key.endsWith('_at') ||
    key.endsWith('_until')
  ) {
    // Value is time
    value = formatDate(value);
  } else if (
    key.includes('amount') ||
    // exclude fee_bearer
    key.endsWith('fee') ||
    key.includes('tax') ||
    key.includes('charge')
  ) {
    value = <Amount value={value} />;
  } else if (
    key === 'merchant_id' ||
    (key === 'entity_id' && attributes.entity_type === 'merchant')
  ) {
    // entity_id & entity_type are returned in "feature" entity
    value = (
      <a class="link" target="_blank" href={`/admin/merchants/${value}`}>
        {value}
      </a>
    );
  } else if (key.endsWith('_id') && key !== 'public_id') {
    // remove _id from tail
    let entityName = key.slice(0, -3);
    let id = prefixEntityValue(entityName, value);
    if (id) {
      value = (
        <a class="link" href={`/admin/entity/${entityName}/${mode}/${id}`}>
          {id}
        </a>
      );
    }
  } else if (!value && typeof value !== 'undefined') {
    value = (
      <span class="square-pills label-pending">{JSON.stringify(value)}</span>
    );
  }

  return value;
}
