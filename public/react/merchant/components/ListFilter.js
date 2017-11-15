import { Component } from 'react';
import { reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import { stringifyQueryParams, getURLQueryParams } from 'rzp/utils/rzp-utils';
import { withRouter } from 'react-router-dom';

@reduxForm({})
@withRouter
export default class ListFilter extends Component {
  // populate the search filters based on query params
  componentWillMount() {
    this.initSearchForm(this.props);
  }

  // update search query
  componentWillReceiveProps(nextProps) {
    if (this.props.location.search !== nextProps.location.search) {
      this.initSearchForm(nextProps);
    }
  }

  initSearchForm(props) {
    let count = props.count;
    let params = {};

    if (count) {
      params['count'] = count;
    }

    if (props.location.search) {
      params = getURLQueryParams(props.location.search);
    }

    this.props.initialize(params);
  }

  // update query params in url before search
  handleOnSubmit = props => {
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
      pathname: this.props.location.pathname,
      search: stringifyQueryParams({}),
    });

    this.props.onClearAnalytics();
  };

  render() {
    let { handleSubmit, onSubmit, form } = this.props;
    return (
      <form
        name={form}
        onSubmit={handleSubmit(this.handleOnSubmit)}
        class="list-filter-container"
      >
        {this.props.children}
        <div class="form-group list-filter-item btn-toolbar">
          <AsyncButton
            class="btn btn-sm btn-default"
            onClick={handleSubmit(this.handleOnSubmit)}
            text="Search"
          />
          <AsyncButton
            class="btn btn-sm btn-link"
            onClick={this.resetForm}
            text="Clear"
          />
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
