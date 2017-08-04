import { Component, PropTypes } from 'react';
import { getURLQueryParams } from 'rzp/utils/rzp-utils';
import { trimDeep } from 'rzp/utils/validators';

export default class ListContainer extends Component {
  static SKIP = 0;
  static COUNT = 25;
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor() {
    super(...arguments);
    this.searchFilters = {};
    this.state = {
      status: {},
    };
  }

  // Do default search based on query params
  componentWillMount() {
    let params = null;

    if (this.props.location.search) {
      params = getURLQueryParams(this.props.location.search);
    }

    this.fetchAll(params);
  }

  fetchAll = (params = {}) => {
    params = { ...params, ...this.getDefaultPageParams() };
    if (params) {
      this.setState(params);
    }

    return this.fetchEntityList(params)
      .then(() => {
        this.setState({
          status: {
            type: 'success',
            message: null,
          },
        });
      })
      .catch(err => {
        this.setState({
          status: {
            type: 'error',
            message: err.errors || err,
          },
        });
      });
  };

  search = params => {
    this.searchFilters = trimDeep(params);
    return this.fetchAll({
      ...this.getDefaultPageParams(),
      ...this.searchFilters,
    });
  };

  paginate = params => {
    let filters = {
      ...this.searchFilters,
      ...params,
    };

    return this.fetchAll({
      ...this.getDefaultPageParams(),
      ...filters,
    });
  };

  getDefaultPageParams() {
    return {
      skip: ListContainer.SKIP,
      count: ListContainer.COUNT,
    };
  }

  fetchEntityList(params) {
    return this.props.fetchAll(params);
  }
}
