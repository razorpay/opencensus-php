import { Component } from 'react';

export default class Pager extends Component {
  constructor() {
    super(...arguments);
    this.onNext = ::this.onNext;
    this.onPrev = ::this.onPrev;
  }

  onNext() {
    let newParams = {
      skip: Number(this.props.skip) + Number(this.props.count),
      count: +this.props.count,
    };
    this.props.onClick(newParams);
  }

  onPrev() {
    let newParams = {
      skip: Number(this.props.skip) - Number(this.props.count),
      count: +this.props.count,
    };
    this.props.onClick(newParams);
  }

  render() {
    let { length, onClick } = this.props;
    let count = +this.props.count;
    let skip = +this.props.skip;
    let nextDisabled = length < count;
    let prevDisabled = !skip;
    let total = skip + length;
    let current = skip + 1;

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
          <div class="btn-group pull-right">
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

        <small class="text-muted">
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
