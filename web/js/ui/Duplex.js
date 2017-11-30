import React, { Component } from 'react';

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
                  let value = result[1];

                  if (typeof value === 'boolean') {
                    value = (
                      <span>
                        <i
                          class={`i ${
                            value ? 'i-yes text-success' : 'i-no text-danger'
                          }`}
                        />
                      </span>
                    );
                  }

                  if (result[0] === 'status') {
                    value = <span class={`pills ${statusLabel(result[1])}`} />;
                  }

                  return (
                    result && (
                      <div class="tr" key={index}>
                        <div class="td">{result[0]}</div>
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
    refunded: 'label-prime',

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
    open: 'label-prime',
    under_review: 'label-pending',
    won: 'label-success',
    lost: 'label-danger',
  };

  return mapper[status];
}
