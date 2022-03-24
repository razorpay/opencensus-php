import React, { Component } from 'react';
import { reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import { stringifyQueryParams, getURLQueryParams } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import { withRouter } from 'react-router-dom';

const DEFAULT_MAX_FILTER_COUNT_DESKTOP = 9;
const DEFAULT_MAX_FILTER_COUNT_MOBILE = 2;

/*
  Default max filter count value added for destop as well as mobile
  implemented a new prop maxMwebFiltersLength for accepting custom
  value for mobile filter length else default value is set
*/

class ListFilter extends Component {
  constructor(props) {
    super(props);

    const MAX_FILTERS = isMobileDevice()
      ? this.props.maxMwebFiltersLength || DEFAULT_MAX_FILTER_COUNT_MOBILE
      : DEFAULT_MAX_FILTER_COUNT_DESKTOP;
    const hasMoreFilters = this.props.children.length > MAX_FILTERS;

    this.state = {
      hasMoreFilters,
      showAllFilters: !hasMoreFilters,
      maxFilterLength: MAX_FILTERS,
    };
  }

  // populate the search filters based on query params
  UNSAFE_componentWillMount() {
    this.initSearchForm(this.props);
  }

  // update search query
  UNSAFE_componentWillReceiveProps(nextProps) {
    if (decodeURI(this.props.location.search) !== decodeURI(nextProps.location.search)) {
      this.initSearchForm(nextProps);
    }
  }

  initSearchForm(props) {
    const count = props.count;
    let params = {};

    if (count) {
      params.count = count;
    }

    if (props.location.search) {
      params = getURLQueryParams(props.location.search);
    }

    for (const key in params) {
      if (params.hasOwnProperty(key)) {
        params[key] = decodeURI(params[key]);
      }
    }

    this.props.initialize(params);
  }

  // update query params in url before search
  handleOnSubmit = (props) => {
    const { date, provider } = this.props;
    if (date) {
      props.from = date.from;
      props.to = date.to;
    }
    if (provider) {
      if (provider.value === 'razorpay') {
        delete props.terminal_id;
        props.settled_by = 'Razorpay';
      } else {
        delete props.settled_by;
        props.terminal_id = provider.value;
      }
    }

    this.props.history.push({
      pathname: this.props.location.pathname,
      hash: this.props.location.hash,
      search: stringifyQueryParams(props),
    });

    this.props.onSearchAnalytics(props, stringifyQueryParams(props));

    return this.props.onSubmit(props);
  };

  // update query params as empty for auto search in willReceiveProps
  resetForm = () => {
    this.props.history.push({
      search: stringifyQueryParams({}),
      hash: this.props.location.hash,
    });

    this.props.reset();

    this.props.onClearAnalytics();

    if (this.props.setProvider) {
      this.props.setProvider({ name: 'All', value: '', gateway: '' });
    }
  };

  render() {
    const { handleSubmit, form } = this.props;
    const { hasMoreFilters, showAllFilters, maxFilterLength } = this.state;

    const filters = this.props.children;
    const visibleFilters = showAllFilters ? filters : filters.slice(0, maxFilterLength);

    return (
      <form
        name={form}
        onSubmit={handleSubmit(this.handleOnSubmit)}
        class={`list-filter-container ${
          this.props.additionalClass ? this.props.additionalClass : ''
        }`}
      >
        {visibleFilters}

        <div class="form-group list-filter-item btn-toolbar">
          {hasMoreFilters && (
            <button
              class="btn btn-sm"
              onClick={() => {
                this.setState({ showAllFilters: !showAllFilters });
              }}
            >
              {showAllFilters ? 'Hide Filters' : 'Show All Filters'}
              <i class={`m-l i i-chevron-${showAllFilters ? 'up' : 'down'}`} />
            </button>
          )}
          <button class="btn btn-primary btn-sm">Search</button>
          <AsyncButton class="btn btn-sm btn-text" onClick={this.resetForm} text="Clear" />
        </div>
      </form>
    );
  }
}

ListFilter.defaultProps = {
  onSubmit: () => {},
  onSearchAnalytics: () => {},
  onClearAnalytics: () => {},
};

export default withRouter(reduxForm({})(ListFilter));
