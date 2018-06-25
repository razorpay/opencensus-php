import React, { Component } from 'react';
import Amount from 'ui/Amount';
import { snakeToTitleCase, formatDate } from 'common/util';
import { statusPill, prefixEntityValue } from 'common/data';

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
                      <div class="tr" key={index} onClick={copyValue}>
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

function copyValue(e) {
  let textEl = document.createElement('textarea');
  textEl.readOnly = true;
  textEl.value = e.currentTarget.querySelector('.td:last-child').innerText;
  document.body.appendChild(textEl);
  textEl.select();
  textEl.setSelectionRange(0, textEl.value.length);
  document.execCommand('copy');
  document.body.removeChild(textEl);
}

function getValue(result, mode, attributes) {
  let [key, value] = result;

  if (!value && typeof value !== 'undefined') {
    value = (
      <span class="square-pills label-pending">{JSON.stringify(value)}</span>
    );
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
  } else if (typeof value === 'number' && /base_amount|fee|tax/.test(key)) {
    // Base Amount is always saved as INR in DB. Fee and Tax are calcualted as pre base amount, so in Rs.
    value = <Amount value={value} />;
  } else if (typeof value === 'number' && /amount|credit|charge/.test(key)) {
    value = <Amount value={value} currency={attributes.currency} />;
  } else if (
    key === 'merchant_id' ||
    (key === 'entity_id' && attributes.entity_type === 'merchant') ||
    (key === 'to_id' && attributes.to_type === 'merchant') ||
    (key === 'source_id' && attributes.source_type === 'merchant')
  ) {
    // *_id & *_type are returned in "feature", "to" and "source" key
    value = (
      <a class="link" target="_blank" href={`/admin/merchants/${value}`}>
        {value}
      </a>
    );
  } else if (
    key.endsWith('_id') &&
    typeof value === 'string' &&
    ['public_id', 'gateway_merchant_id', 'gateway_terminal_id'].indexOf(key) ===
      -1
  ) {
    // remove _id from tail
    let entityName = key.slice(0, -3);
    if (key === 'recipient_settlement_id') {
      entityName = 'settlement';
    } else if (key === 'source_id') {
      entityName = attributes.source_type;
    } else if (key === 'to_id') {
      entityName = attributes.to_type;
    }

    let id = prefixEntityValue(entityName, value);
    if (entityName === 'file') {
      entityName = 'file_store';
    }
    value = (
      <a class="link" href={`/admin/entity/${entityName}/${mode}/${id}`}>
        {id}
      </a>
    );
  }

  return value;
}
