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
                  var result = itemFn(model);
                  return (
                    result && (
                      <div class="tr" key={index}>
                        <div class="td">{result[0]}</div>
                        <div class="td text-right">{result[1]}</div>
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
