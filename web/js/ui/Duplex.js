import React, { Component } from 'react';

export default class Duplex extends Component {
  render() {
    let { fields, pending, model } = this.props;

    return (
      <div>
        {(pending && <div class="table-pending" />) ||
          (fields.length && (
            <div class="table table-striped table-bordered">
              {fields.map((itemFn, index) => {
                var result = itemFn(model);
                return (
                  result && (
                    <div class="tr" key={index}>
                      <div class="td">{result[0]}</div>
                      <div class="td">{result[1]}</div>
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
