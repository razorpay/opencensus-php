import React, { Component } from 'react';
import { observer } from 'mobx-react';
import { Link } from 'react-router-dom';

import ClotSearch from 'razorx/components/ui/ClotSearch';
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
  header = true,
  indexFn = defaultIndexFn,
  customClass = '',
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
    return <div className="spinner center" />;
  }

  if (!items || !items.length) {
    return <div className={`table-empty ${customClass}`} />;
  }

  return (
    <div className={`table-container ${customClass}`}>
      <TransitionGroup className={tableClass} enter={animateRow} exit={animateRow}>
        {header && (
          <CSSTransition timeout={0}>
            <div className="tr thead">
              {fields.map((field, index) => (
                <div className="th" key={index}>
                  {field[0]}
                </div>
              ))}
            </div>
          </CSSTransition>
        )}
        {items.map((item, index) => {
          return (
            <CSSTransition key={indexFn(item, index, items)} classNames="row" timeout={animObj}>
              <Row className={rowClass} onClick={onClick && onClick.bind(item)} to={href && href(item)}>
                {fields.map((field, index) => (
                  <Value key={index} index={index} item={item} valueFn={field[1]} />
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
    return <div className="td">{this.props.valueFn(this.props.item, this.props.index)}</div>;
  }
}

export const DataTable = observer(Table);

@observer
export class PageTable extends Component {
  static defaultProps = {
    searchLabel: 'Filters',
  };
  state = { searchQuery: '' };
  ClotSearch = new ClotSearch(250);

  handleSearchQuery = (e) => {
    let searchQuery = e.target.value;

    this.ClotSearch.startClotCycle(() => {
      this.setState({ searchQuery });
    });
  };

  render() {
    let { model, info = true, title, searchFilters, ...props } = this.props;
    let { pending, items, filters, animateItems } = model;

    pending = pending.fetch;

    let displayItems = items;

    if (searchFilters && this.state.searchQuery) {
      displayItems = items.filter((item) => {
        let matched = false;

        for (let i = 0; i < searchFilters.length; i++) {
          if (
            item[searchFilters[i]] &&
            item[searchFilters[i]].toLowerCase().indexOf(this.state.searchQuery.toLowerCase()) !==
              -1
          ) {
            matched = true;
            break;
          }
        }

        return matched;
      });
    }

    // animateRow = false because animation is causing rendering issues when search filter is there
    if (!pending && items && items.length) {
      return (
        <div className="box">
          <Pagination model={model} />
          {title && <header>{title}</header>}

          {info && (
            <div className="table-header">
              <span className="table-info">
                Results {filters.skip + 1} &ndash; {filters.skip + items.length}
              </span>
              {searchFilters && (
                <div className="pull-right">
                  {props.searchLabel}:
                  <input className="m-l" onChange={this.handleSearchQuery} />
                </div>
              )}
            </div>
          )}
          <Table
            animateRow={searchFilters ? false : animateItems}
            items={displayItems}
            {...props}
          />
        </div>
      );
    }
    return <Table pending={pending} />;
  }
}

class Pagination extends Component {
  prev() {
    this.applyFilters({
      ...this.filters,
      skip: Number(this.filters.skip) - Number(this.filters.count),
    });
  }

  next() {
    this.applyFilters({
      ...this.filters,
      skip: Number(this.filters.skip) + Number(this.filters.count),
    });
  }

  render() {
    let model = this.props.model;
    return (
      <div className="pagination">
        {(model.filters.skip && (
          <div className="prev" onClick={this.prev.bind(model)}>
            ← Previous
          </div>
        )) ||
          null}
        {(model.filters.count == model.items.length && (
          <div className="next" onClick={this.next.bind(model)}>
            Next →
          </div>
        )) ||
          null}
      </div>
    );
  }
}
