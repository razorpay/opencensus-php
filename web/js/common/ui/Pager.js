import { Component } from 'react';

import { classList } from 'common/utils/rzp-utils';
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
    const { length } = this.props;
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
        class="clearfix text-center"
        style={{
          margin: '20px',
        }}
      >
        {!(nextDisabled && prevDisabled) ? (
          <div className={classList('btn-group pull-right', this.props.buttonClass)}>
            <button
              type="button"
              class="btn btn-default btn-sm i"
              disabled={prevDisabled}
              onClick={this.onPrev}
            >
              <i class="i i-chevron-left" />
            </button>
            <button
              type="button"
              class="btn btn-default btn-sm i"
              disabled={nextDisabled}
              onClick={this.onNext}
            >
              <i class="i i-chevron-right" />
            </button>
          </div>
        ) : null}

        <small className={classList('text-muted', this.props.textClass)}>
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
