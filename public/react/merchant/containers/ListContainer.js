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

  defaultSearch(queryString) {
    let params = null;

    if (queryString) {
      params = getURLQueryParams(queryString);
    }

    this.fetchAll(params);
  }

  // Do default search based on query params
  componentWillMount() {
    this.defaultSearch(this.props.location.search);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.location.search !== nextProps.location.search) {
      this.defaultSearch(nextProps.location.search);
    }
  }

  fetchAll = (params = {}) => {
    params = { ...this.getDefaultPageParams(), ...params };
    if (params) {
      this.setState(params);
    }

    // props.fetchAll is available only when model is implemented. Addons doesn't have model hence calling 'fetchList' class fn.
    if (this.props.fetchAll || this.fetchEntityList) {
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
    } else if (this.fetchList) {
      this.fetchList(params);
    }
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
