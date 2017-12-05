import React, { Component } from 'react';
import Amount from 'ui/Amount';
import { snakeToTitleCase, formatDate } from 'util/index';

const defaultClass = 'table table-striped';

export default class Duplex extends Component {
  render() {
    let { fields, pending, model } = this.props;

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
                  let value = getValue(result, model.mode);

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

function statusLabel(status) {
  var mapper = {
    // Common
    created: 'label-semi-muted',
    failed: 'label-danger',

    // payment
    authorized: 'label-info',
    captured: 'label-success',
    refunded: 'label-primary',

    // order
    attempted: 'label-info',
    paid: 'label-success',

    // settlement
    processed: 'label-success',

    // billdesk
    cancelled: 'label-danger',
    null: 'label-pending',

    // batch
    processing: 'label-info',

    // refund
    partial: 'label-info', // payment.refund_status

    // invoice
    draft: 'label-semi-muted',
    issued: 'label-info',
    expired: 'label-danger',

    // dispute
    open: 'label-primary',
    under_review: 'label-pending',
    won: 'label-success',
    lost: 'label-danger',
  };

  return mapper[status];
}

function getPrefixType(entityType) {
  switch (entityType) {
    case 'balance_account':
      return 'ba_';
    case 'balance_transfer':
      return 'bt_';
    case 'card':
      return 'card_';
    case 'customer':
      return 'cust_';
    case 'dispute':
      return 'dispute_';
    case 'payment':
      return 'pay_';
    case 'offer':
      return 'offer_';
    case 'order':
      return 'order_';
    case 'plan':
      return 'plan_';
    case 'refund':
      return 'rfnd_';
    case 'reversal':
      return 'rvrsl_';
    case 'settlement':
      return 'setl_';
    case 'subscription':
      return 'sub_';
    case 'token':
      return 'tkn_';
    case 'transaction':
      return 'txn_';
    case 'transfer':
      return 'trf_';
    case 'virtual_account':
      return 'va_';

    default:
      return '';
  }
}

function getValue(result, mode) {
  let value = result[1];

  if (typeof value === 'boolean') {
    value = (
      <span>
        <i class={`i ${value ? 'i-yes text-success' : 'i-no text-danger'}`} />
      </span>
    );
  } else if (result[0] === 'status') {
    value = <span class={`pills ${statusLabel(result[1])}`}>{result[1]}</span>;
  } else if (
    result[0].indexOf('amount') > -1 ||
    result[0].indexOf('fee') > -1 ||
    result[0].indexOf('tax') > -1 ||
    result[0].indexOf('charge') > -1
  ) {
    value = <Amount value={value} />;
  } else if (result[0].split('_').indexOf('at') > -1) {
    // Value is time
    value = formatDate(value);
  } else if (value && result[0] === 'merchant_id') {
    value = (
      <a class="link" target="_blank" href={`/admin/merchants/${value}`}>
        {value}
      </a>
    );
  } else if (
    value &&
    result[0].indexOf('_id') > -1 &&
    result[0] !== 'public_id'
  ) {
    let entityName = result[0].match(/(.+)(?:_id)/)[1];
    let id = getPrefixType(entityName) + value;
    value = (
      <a class="link" href={`/admin/entity/${entityName}/${mode}/${id}`}>
        {id}
      </a>
    );
  } else if (!value && typeof value !== 'undefined') {
    value = (
      <span class="square-pills label-pending">{JSON.stringify(value)}</span>
    );
  }

  return value;
}
