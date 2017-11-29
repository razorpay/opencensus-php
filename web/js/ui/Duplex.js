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
