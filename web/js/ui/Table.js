import React, { Component } from 'react';
import { observer } from 'mobx-react';

import TransitionGroup from 'react-transition-group/TransitionGroup';
import CSSTransition from 'react-transition-group/CSSTransition';

const animObj = {
  enter: 1000,
  exit: 700,
};

function defaultIndexFn(item, index, array) {
  return item.id || array.length - index;
}

export default function Table({
  pending,
  fields,
  items,
  onClick,
  bordered,
  animateRow,
  indexFn = defaultIndexFn,
}) {
  let trClass = onClick ? 'tr clickable' : 'tr';
  let tableClass = 'table table-striped';
  if (bordered) {
    tableClass += ' table-bordered';
  }

  if (pending) {
    return <div class="table-pending" />;
  }

  if (!items || !items.length) {
    return <div class="table-empty" />;
  }

  return (
    <div class="table-container">
      <TransitionGroup class={tableClass} enter={animateRow} exit={animateRow}>
        <CSSTransition timeout={0}>
          <div class="tr thead">
            {fields.map((field, index) => (
              <div class="th" key={index}>
                {field[0]}
              </div>
            ))}
          </div>
        </CSSTransition>
        {items.map((item, index) => {
          return (
            <CSSTransition
              key={indexFn(item, index, items)}
              classNames="row"
              timeout={animObj}
            >
              <div class={trClass} onClick={onClick && item::onClick}>
                {fields.map((field, index) => (
                  <div class="td" key={index}>
                    {field[1](item)}
                  </div>
                ))}
              </div>
            </CSSTransition>
          );
        })}
      </TransitionGroup>
    </div>
  );
}

export const DataTable = observer(Table);

@observer
export class PageTable extends Component {
  render() {
    let { fields, model, onClick, info = true, title } = this.props;
    let { pending, items, filters, animateItems } = model;

    pending = pending.fetch;

    if (!pending && items && items.length) {
      return (
        <div class="box">
          <Pagination model={model} />
          {title && `${title} · `}
          {info && (
            <div class="table-info">
              Results {filters.skip + 1} &ndash; {filters.skip + items.length}
            </div>
          )}
          <Table
            animateRow={animateItems}
            fields={fields}
            onClick={onClick}
            items={items}
          />
        </div>
      );
    }
    return <Table pending={pending} />;
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
