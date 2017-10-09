import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Form from 'ui/Form';

@observer
export default class Table extends Component {
  render() {
    let { fields, model } = this.props;
    let { pending, items, filters } = model;

    pending = pending.get();
    filters = filters.get();

    return (
      <div>
        {(pending && <div class="table-pending" />) ||
          ((items.length && (
            <div class="box">
              <div class="table-info">
                {items.length} Results ({filters.skip + 1} &ndash;{' '}
                {filters.skip + items.length})
              </div>
              <div class="table table-striped">
                <div class="tr thead">
                  {fields.map((field, index) => (
                    <div class="th" key={index}>
                      {field[0]}
                    </div>
                  ))}
                </div>
                {items.map((item, index) => {
                  return (
                    <div class="tr" key={index}>
                      {fields.map((field, index) => (
                        <div class="td" key={index}>
                          {field[1](item)}
                        </div>
                      ))}
                    </div>
                  );
                })}
              </div>
            </div>
          )) || <div class="table-empty" />)}
      </div>
    );
  }
}
