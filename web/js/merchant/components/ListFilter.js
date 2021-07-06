import React, { Component } from 'react';
import { reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import { stringifyQueryParams, getURLQueryParams } from 'common/utils/rzp-utils';
import { withRouter } from 'react-router-dom';

class ListFilter extends Component {
  constructor(props) {
    super(props);

    const MAX_FILTERS = 9;
    const hasMoreFilters = this.props.children.length >= MAX_FILTERS;

    this.state = {
      hasMoreFilters,
      showAllFilters: !hasMoreFilters,
    };
  }

  // populate the search filters based on query params
  componentWillMount() {
    this.initSearchForm(this.props);
  }

  // update search query
  componentWillReceiveProps(nextProps) {
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

    for (let k in params) {
      if (params.hasOwnProperty(k)) {
        params[k] = decodeURI(params[k]);
      }
    }

    this.props.initialize(params);
  }

  // update query params in url before search
  handleOnSubmit = (props) => {
    const date = this.props.date;
    if (date) {
      props.from = date.from;
      props.to = date.to;
    }

    this.props.history.push({
      pathname: this.props.location.pathname,
      search: stringifyQueryParams(props),
    });

    this.props.onSearchAnalytics(props, stringifyQueryParams(props));

    return this.props.onSubmit(props);
  };

  // update query params as empty for auto search in willReceiveProps
  resetForm = () => {
    this.props.history.push({
      search: stringifyQueryParams({}),
    });

    this.props.reset();

    this.props.onClearAnalytics();
  };

  render() {
    const { handleSubmit, form } = this.props;
    const { hasMoreFilters, showAllFilters } = this.state;

    const filters = this.props.children;
    const visibleFilters = showAllFilters ? filters : filters.slice(0, 8);

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
          <AsyncButton class="btn btn-sm btn-link" onClick={this.resetForm} text="Clear" />
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
