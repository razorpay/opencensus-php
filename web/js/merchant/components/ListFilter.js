/* eslint-disable react/no-unsafe */
import React, { Component } from 'react';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { reduxForm } from 'redux-form';

import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import { isExperimentEnabled, isInternalTestingEnabled } from 'common/splitz/utils';
import {
  stringifyQueryParams,
  getURLQueryParams,
  encodeSensitiveFields,
  decodeSensitiveFields,
} from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';

// Keep this util here, will break web/js/merchant/views/Transactions/v2/common/__tests__/utils.test.js testcases
export const isTransactionsV2Enabled = (splitz, user) => {
  const { abExperiments } = splitz || { abExperiments: { Transactions_Revamp: undefined } };

  if (!abExperiments?.Transactions_Revamp) return false;

  // All optimiser merchants are parity merchants
  // All merchants whose org is Curlec are parity merchants
  // All merchants whose org is VAS are parity merchants
  const isExcludedMerchant = user.isFeatureEnabled('raas') || !user.isOrgRZP;
  const isTransactionsEnabledForExcludedMerchant = isExperimentEnabled(
    abExperiments?.enable_trxn_v2_for_excluded_merchants,
  );

  // for excluded merchants, if experiment is enabled, then show trxn v2
  if (isExcludedMerchant) {
    if (isTransactionsEnabledForExcludedMerchant) {
      return true;
    }
    return false;
  }

  return (
    Boolean(user.isCountryIndia || user.isCountrySingapore) &&
    isExperimentEnabled(abExperiments.Transactions_Revamp)
  );
};

const DEFAULT_MAX_FILTER_COUNT_DESKTOP = 10;
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

    /**
     * Expose redux-form change function to parent
     */
    if (props.setChangeFunction && props.change) {
      props.setChangeFunction(props.change);
    }
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

    this.props.initialize(decodeSensitiveFields(params));
    if (this.props.setInitialFormData) {
      this.props.setInitialFormData(decodeSensitiveFields(params));
    }
  }

  // update query params in url before search
  handleOnSubmit = (props) => {
    const {
      date,
      provider,
      filtersToHideInQueryParams,
      user,
      splitz,
      history,
      location: { pathname, hash, state },
    } = this.props;
    props = encodeSensitiveFields(props);
    if (date) {
      props.from = date.from;
      props.to = date.to;
    }
    if (provider) {
      if (provider.value === 'razorpay') {
        delete props.terminal_id;
        props.settled_by = 'Razorpay';
      } else if (provider.value || provider.value === '') {
        delete props.settled_by;
        props.terminal_id = provider.value;
      }
    }

    //remove these filters from query params
    let queryParamsProps = { ...props };
    if (filtersToHideInQueryParams?.length) {
      queryParamsProps = Object.keys(queryParamsProps).reduce((result, key) => {
        if (!filtersToHideInQueryParams.includes(key)) {
          result[key] = queryParamsProps[key];
        }
        return result;
      }, {});
    }

    const historyObject = {
      pathname,
      hash,
      state,
      search: stringifyQueryParams(queryParamsProps),
    };
    if (isTransactionsV2Enabled(splitz, user)) {
      history.replace(historyObject);
    } else {
      history.push(historyObject);
    }

    this.props.onSearchAnalytics(props, stringifyQueryParams(queryParamsProps));

    return this.props.onSubmit(props);
  };

  // update query params as empty for auto search in willReceiveProps
  resetForm = () => {
    const {
      history,
      location: { hash, state },
      resetHandler,
      reset,
      onClearAnalytics,
      setProvider,
      user,
      splitz,
    } = this.props;

    const historyObject = {
      search: stringifyQueryParams({}),
      hash,
      state,
    };
    if (isTransactionsV2Enabled(splitz, user)) {
      history.replace(historyObject);
    } else {
      history.push(historyObject);
    }

    if (resetHandler) {
      resetHandler();
    }

    reset();

    onClearAnalytics();

    if (setProvider) {
      setProvider({ name: 'All', value: '', gateway: '' });
    }
  };

  render() {
    const { handleSubmit, form, isNewFilter } = this.props;
    const { hasMoreFilters, showAllFilters, maxFilterLength } = this.state;

    const filters = this.props.children;
    const visibleFilters = showAllFilters ? filters : filters.slice(0, maxFilterLength);
    const DivButtonWrapper = ({ children }) => (
      <div className="filter-buttons-wrapper">{children}</div>
    );
    const ButtonWrapper = isNewFilter ? DivButtonWrapper : React.Fragment;

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
          <ButtonWrapper>
            <button class="btn btn-primary btn-sm">Search</button>
            <AsyncButton class="btn btn-sm btn-text" onClick={this.resetForm} text="Clear" />
          </ButtonWrapper>
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

export default compose(
  withSplitzService,
  connect((state) => ({
    user: state.session.user,
  })),
  reduxForm({}),
)(withRouter(ListFilter));
