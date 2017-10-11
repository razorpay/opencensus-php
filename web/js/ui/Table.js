import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Form from 'ui/Form';

@observer
export default class Table extends Component {
  render() {
    let { fields, model, onSubmit, onClick } = this.props;
    let { pending, items, filters } = model;

    let Tr = onSubmit ? Form : 'div';
    let trClass = onClick ? 'tr clickable' : 'tr';

    pending = pending.get();

    return (
      <div>
        {(pending && <div class="table-pending" />) ||
          ((items.length && (
            <div class="box">
              <Pagination model={model} />
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
                    <Tr
                      class={trClass}
                      key={index}
                      onSubmit={onSubmit && item::onSubmit}
                      onClick={onClick && item::onClick}
                    >
                      {fields.map((field, index) => (
                        <div class="td" key={index}>
                          {field[1](item)}
                        </div>
                      ))}
                    </Tr>
                  );
                })}
              </div>
            </div>
          )) || <div class="table-empty" />)}
      </div>
    );
  }
}

class Pagination extends Component {
  prev() {
    this.skip -= this.count;
  }

  next() {
    this.skip += this.count;
  }

  render() {
    let { filters, items } = this.props.model;
    return (
      <div class="pagination">
        {(filters.skip && (
          <div class="prev" onClick={filters::this.prev}>
            ← Previous
          </div>
        )) ||
          null}
        {(filters.count === items.length && (
          <div class="next" onClick={filters::this.next}>
            Next →
          </div>
        )) ||
          null}
      </div>
    );
  }
}
