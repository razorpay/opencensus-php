import { Component } from 'react';
import PropTypes from 'prop-types';
import { getURLQueryParams } from 'rzp/utils/rzp-utils';
import { trimDeep } from 'rzp/utils/validators';

export default class ListContainer extends Component {
  static SKIP = 0;
  static COUNT = 25;
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props, ...args) {
    super(props, ...args);
    this.searchFilters = {};

    const searchString = props.location.search.trim();

    if (searchString) {
      const params = getURLQueryParams(searchString);

      this.searchFilters = this.removeBlacklistedParams(params, props);
    }

    this.state = {
      status: {},
    };
  }

  removeBlacklistedParams(params, props = this.props) {
    const { blacklistQueryParams = [] } = props,
      hasBlacklistQueryParams = blacklistQueryParams.length > 0;

    if (!hasBlacklistQueryParams) {
      return params;
    }

    return Object.keys(params).reduce((result, paramKey) => {
      const isParamBlacklisted = blacklistQueryParams.indexOf(paramKey) >= 0;

      if (!isParamBlacklisted) {
        result[paramKey] = params[paramKey];
      }

      return result;
    }, {});
  }

  defaultSearch(queryString) {
    let params = null;

    if (queryString) {
      params = getURLQueryParams(queryString);
      params = this.removeBlacklistedParams(params);
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
  sanitizeParams(params) {
    if (!params) {
      throw 'Params must be an object';
    }

    const keys = Object.keys(params);
    if (!keys.length) {
      return;
    }

    keys.forEach(key => {
      params[key] = encodeURIComponent(params[key]);
    });
  }

  fetchAll = (params = {}) => {
    params = { ...this.getDefaultPageParams(), ...params };
    params = this.removeBlacklistedParams(params);

    this.sanitizeParams(params);

    this.setState(params);

    // props.fetchAll is available only when model is implemented. Addons doesn't have model hence calling 'fetchList' class fn.
    if (!this.props.fetchAll && this.fetchList) {
      this.fetchList(params);
    } else if (this.props.fetchAll || this.fetchEntityList) {
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
