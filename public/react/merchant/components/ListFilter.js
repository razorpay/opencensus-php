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
    let count = this.props.count;
    let params = {};

    if (count) {
      params['count'] = count;
    }

    if (this.props.location.search) {
      params = getURLQueryParams(this.props.location.search);
    }

    this.props.initialize(params);
  }

  // update query params in url before search
  handleOnSubmit = props => {
    this.props.history.push({
      pathname: this.props.location.pathname,
      search: stringifyQueryParams(props),
    });

    return this.props.onSubmit(props);
  };

  render() {
    let { handleSubmit, onSubmit, reset, form } = this.props;
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
            onClick={reset}
            text="Clear"
          />
        </div>
      </form>
    );
  }
}

ListFilter.defaultProps = {
  onSubmit: () => {},
};
