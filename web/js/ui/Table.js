import React, { Component } from 'react';
import { observer } from 'mobx-react';
import { Link } from 'react-router-dom';

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
  stripe = true,
  border,
  animateRow,
  href,
  indexFn = defaultIndexFn,
}) {
  let Row = href ? Link : 'div';
  let rowClass = href || onClick ? 'tr clickable' : 'tr';

  let tableClass = 'table';
  if (stripe) {
    tableClass += ' table-striped';
  }
  if (border) {
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
              <Row
                class={rowClass}
                onClick={onClick && item::onClick}
                to={href && href(item)}
              >
                {fields.map((field, index) => (
                  <Value key={index} item={item} valueFn={field[1]} />
                ))}
              </Row>
            </CSSTransition>
          );
        })}
      </TransitionGroup>
    </div>
  );
}

@observer
class Value extends Component {
  render() {
    return <div class="td">{this.props.valueFn(this.props.item)}</div>;
  }
}

export const DataTable = observer(Table);

@observer
export class PageTable extends Component {
  render() {
    let { model, info = true, title, ...props } = this.props;
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
          <Table animateRow={animateItems} items={items} {...props} />
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
