import React, { Component } from 'react';
import { observer } from 'mobx-react';

export default function Table({ fields, items, onClick, bordered }) {
  let trClass = onClick ? 'tr clickable' : 'tr';
  let tableClass = 'table table-striped';
  if (bordered) {
    tableClass += ' table-bordered';
  }

  return (
    <div class="table-container">
      {items && items.length ? (
        <div class={tableClass}>
          <div class="tr thead">
            {fields.map((field, index) => (
              <div class="th" key={index}>
                {field[0]}
              </div>
            ))}
          </div>
          {items.map((item, index) => {
            return (
              <div
                class={trClass}
                key={index}
                onClick={onClick && item::onClick}
              >
                {fields.map((field, index) => (
                  <div class="td" key={index}>
                    {field[1](item)}
                  </div>
                ))}
              </div>
            );
          })}
        </div>
      ) : (
        <div class="table-empty" />
      )}
    </div>
  );
}

export const DataTable = observer(Table);

@observer
export class PageTable extends Component {
  render() {
    let { fields, model, onClick } = this.props;
    let { pending, items, filters } = model;

    pending = pending.fetch;

    return (
      <div>
        {(pending && <div class="table-pending" />) ||
          ((items &&
            items.length && (
              <div class="box">
                <Pagination model={model} />
                <div class="table-info">
                  {items.length} Results ({filters.skip + 1} &ndash;{' '}
                  {filters.skip + items.length})
                </div>
                <Table fields={fields} onClick={onClick} items={items} />
              </div>
            )) || <div class="table-empty" />)}
      </div>
    );
  }
}

class Pagination extends Component {
  prev() {
    this.setFilters({
      skip: this.filters.skip - this.filters.count,
    });
  }

  next() {
    this.setFilters({
      skip: this.filters.skip + this.filters.count,
    });
  }

  render() {
    let model = this.props.model;
    return (
      <div class="pagination">
        {(model.filters.skip && (
          <div class="prev" onClick={model::this.prev}>
            ← Previous
          </div>
        )) ||
          null}
        {(model.filters.count === model.items.length && (
          <div class="next" onClick={model::this.next}>
            Next →
          </div>
        )) ||
          null}
      </div>
    );
  }
}
