import React, { Component } from 'react';

export default class Pager extends Component {
  constructor(...args) {
    super(...args);
    this.onNext = this.onNext.bind(this);
    this.onPrev = this.onPrev.bind(this);
  }

  onNext() {
    const newParams = {
      skip: Number(this.props.skip) + Number(this.props.count),
      count: +this.props.count,
    };

    this.props.onClick(newParams, 'next');
  }

  onPrev() {
    const newParams = {
      skip: Number(this.props.skip) - Number(this.props.count),
      count: +this.props.count,
    };

    this.props.onClick(newParams, 'prev');
  }

  render() {
    const { length, hasMoreData = true } = this.props;
    const count = +this.props.count;
    const skip = +this.props.skip;
    const nextDisabled = length < count;
    const prevDisabled = !skip;
    const total = skip + length;
    const current = skip + 1;

    if (!total) {
      return null;
    }

    return (
      <div
        className="clearfix text-center pager"
        style={{
          margin: '20px',
        }}
      >
        {!(nextDisabled && prevDisabled) ? (
          <div className="btn-group pull-right">
            <button
              type="button"
              className="btn btn-default btn-sm i"
              disabled={prevDisabled}
              onClick={this.onPrev}
            >
              <i className="i i-chevron-left" />
            </button>
            <button
              type="button"
              className="btn btn-default btn-sm i"
              disabled={nextDisabled || !hasMoreData}
              onClick={this.onNext}
            >
              <i className="i i-chevron-right" />
            </button>
          </div>
        ) : null}

        <small className="text-muted">
          Showing {current} - {total}
        </small>
      </div>
    );
  }
}

Pager.defaultProps = {
  count: 25,
  skip: 0,
};
