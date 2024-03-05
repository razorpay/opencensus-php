// core
import { Component } from 'react';
import moment from 'moment';
import PropTypes from 'prop-types';

// utils
import { getURLQueryParams, stringifyQueryParams } from '@dashboard/shared-utils/rzp-utils';
import { trimDeep } from '@dashboard/shared-utils/validators';

class ListContainer extends Component {
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
      /*
        As this is a common class which is extended in many places so other inherted components
        might be using this status state so ignoring this eslint Error
      */
      // eslint-disable-next-line react/no-unused-state
      status: {},
    };
  }

  removeBlacklistedParams(params, props = this.props) {
    const { blacklistQueryParams = [] } = props;
    const hasBlacklistQueryParams = blacklistQueryParams.length > 0;

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
      if (params) {
        params = this.removeBlacklistedParams(params);
      }
    }

    this.fetchAll(params);
  }

  // Do default search based on query params
  UNSAFE_componentWillMount() {
    this.defaultSearch(this.props.location.search);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (decodeURI(this.props.location.search) !== decodeURI(nextProps.location.search)) {
      this.defaultSearch(nextProps.location.search);
    }
  }

  fetchAll = (params = {}, fetchFA) => {
    const {
      location: { pathname },
    } = this.props;
    params = { ...this.getDefaultPageParams(), ...params };
    params = this.removeBlacklistedParams(params);

    // HOTFIX: temporary, default to 7 days for loading payments if there is no from and to in the URL
    const isPathIncluded = [
      '/payments',
      '/failed-payments',
      '/payments/b2b-exports',
      '/payments/invoices',
      '/refunds',
    ].includes(pathname);
    if (isPathIncluded && !params?.from && !params?.to) {
      params.from = moment().add(-7, 'd').startOf('day').unix();
      params.to = moment().endOf('day').unix();
    }

    if (pathname === '/disputes' && !params?.from && !params?.to) {
      params.from = moment().add(-90, 'd').startOf('day').unix();
      params.to = moment().endOf('day').unix();
    }

    this.setState(params);

    if (params.id) {
      params.id = encodeURIComponent(params.id); // Encoding just id. Rest are query params, which is encoded while making axios request
    }

    for (const k in params) {
      if (params.hasOwnProperty(k)) {
        params[k] = decodeURI(params[k]);
      }
    }

    // check if Failure Analysis call is getting made then call it directly
    if (fetchFA) {
      // modifying params and also validating if the days > 90 so don't call the FA api at all as api will not respond
      if (params) {
        delete params?.count; // api don't support this params
        delete params?.skip; // api don't support this params
        const fromDate = moment(parseInt(params.from, 10) * 1000);
        const toDate = moment(parseInt(params.to, 10) * 1000);
        const dateDiff = toDate.diff(fromDate, 'days');
        if (dateDiff <= 90) {
          return this.fetchFA(params);
        } else {
          this.props.resetFA();
        }
      }
      return null;
    }
    // props.fetchAll is available only when model is implemented. Addons doesn't have model hence calling 'fetchList' class fn.
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-nocheck
    if (!this.props.fetchAll && this.fetchList) {
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-nocheck
      this.fetchList(params);
    } else if (this.props.fetchAll || this.fetchEntityList) {
      const adjustedParams = { ...params };
      if (adjustedParams?.txn_receiver_type) {
        adjustedParams.notes = adjustedParams.txn_receiver_type;
      }
      delete adjustedParams.txn_receiver_type;

      const promise = this.fetchEntityList(adjustedParams);
      if (promise?.then) {
        promise
          .then(() => {
            this.setState({
              /*
                As this is a common class which is extended in many places so other inherted components
                might be using this status state so ignoring this eslint Error
              */
              // eslint-disable-next-line react/no-unused-state
              status: {
                type: 'success',
                message: null,
              },
            });
          })
          .catch((err) => {
            this.setState({
              /*
                As this is a common class which is extended in many places so other inherted components
                might be using this status state so ignoring this eslint Error
              */
              // eslint-disable-next-line react/no-unused-state
              status: {
                type: 'error',
                message: err.errors || err,
              },
            });
          });
      }

      return promise;
    }
    // if all the above if conditions are violated we can return null
    return null;
  };
  // eslint-disable-next-line react/no-unused-class-component-methods
  search = (params) => {
    this.searchFilters = trimDeep(params);
    return this.fetchAll({
      ...this.getDefaultPageParams(),
      ...this.searchFilters,
    });
  };

  // eslint-disable-next-line react/no-unused-class-component-methods
  analizeFailure = (params) => {
    const failureAnalysisFilter = trimDeep(params);
    return this.fetchAll(
      {
        ...failureAnalysisFilter,
      },
      true,
    );
  };

  // eslint-disable-next-line react/no-unused-class-component-methods
  paginate = (params) => {
    const filters = {
      ...this.searchFilters,
      ...params,
    };

    return this.fetchAll({
      ...this.getDefaultPageParams(),
      ...filters,
    });
  };

  getDefaultPageParams() {
    const {
      location: { pathname },
    } = this.props;
    const defaultProps = {
      skip: ListContainer.SKIP,
      count: ListContainer.COUNT,
    };
    if (pathname === '/failed-payments') {
      defaultProps.status = 'failed';
    }
    return defaultProps;
  }

  fetchEntityList(params) {
    return this.props.fetchAll?.(params);
  }

  fetchFA(params) {
    return this.props.fetchFA?.({
      url: `merchants/payments/failure_analysis${stringifyQueryParams(params)}`,
    });
  }
}

export default ListContainer;
