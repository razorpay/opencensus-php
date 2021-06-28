import { Component } from 'react';
import PropTypes from 'prop-types';
import { getURLQueryParams } from 'common/utils/rzp-utils';
import { trimDeep } from 'common/utils/validators';
import moment from 'moment';

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
    if (
      decodeURI(this.props.location.search) !==
      decodeURI(nextProps.location.search)
    ) {
      this.defaultSearch(nextProps.location.search);
    }
  }

  fetchAll = (params = {}) => {
    params = { ...this.getDefaultPageParams(), ...params };
    params = this.removeBlacklistedParams(params);

    // HOTFIX: temporary, default to 7 days for loading payments if there is no from and to in the URL
    if (this.props.location.pathname === '/payments' && !params?.from && !params?.to) {
      params.from = moment().add(-7, 'd').startOf('day').unix();
      params.to = moment().endOf('day').unix();
    }

    this.setState(params);

    if (params.id) {
      params.id = encodeURIComponent(params.id); // Encoding just id. Rest are query params, which is encoded while making axios request
    }

    for (let k in params) {
      if (params.hasOwnProperty(k)) {
        params[k] = decodeURI(params[k]);
      }
    }

    // props.fetchAll is available only when model is implemented. Addons doesn't have model hence calling 'fetchList' class fn.
    if (!this.props.fetchAll && this.fetchList) {
      this.fetchList(params);
    } else if (this.props.fetchAll || this.fetchEntityList) {
      const promise = this.fetchEntityList(params);

      if (promise.then) {
        promise
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

      return promise;
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
