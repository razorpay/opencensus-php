import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Form from 'ui/Form';
import SimpleTable from 'ui/SimpleTable';

@observer
export default class Table extends Component {
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
                <SimpleTable fields={fields} onClick={onClick} items={items} />
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
