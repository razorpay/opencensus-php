import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import { NavLink } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';
import Spinner from 'rzp/ui/Spinner';
import ListContainer from 'merchant/containers/ListContainer';
import ListFilter from 'merchant/components/ListFilter';
import { fetchPaymentPagesList } from '../model';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';
import { showNotification } from 'rzp/modules/notifications';
import ShowWhen from 'merchant/components/ShowWhen';

import PaymentPagesV2List from './V2';
import PaymentPagesV3List from './V3';

import { populateRPLReduxList } from 'merchant/modules/invoices/list';

import OnboardingPP from '../OnboardingPP';
import { trackListActions } from '../ga';

@connect(state => ({ ...state.invoices, ...state.session }), {
  showNotification,
  populateRPLReduxList,
})
export default class PaymentPagesContainer extends ListContainer {
  state = {
    loading: true,
    loadingAllList: true,
  };

  componentDidMount() {
    this.fetchAllEntityList();
  }

  componentWillReceiveProps(nextProps, nextState) {
    if (this.props.paymentPages.length !== nextProps.paymentPages.length) {
      const newLength = nextProps.paymentPages.length;

      if (newLength) {
        this.setState({
          totalPaymentPagesLength: newLength,
        });
      }
    }

    super.componentWillReceiveProps(nextProps);
  }

  /* Fetch all payment pages list to find whether first-time user */
  fetchAllEntityList() {
    fetchPaymentPagesList({
      count: 1,
    })
      .then(resp => {
        this.setState({
          loadingAllList: false,
        });

        if (resp.data) {
          this.setState({
            totalPaymentPagesLength: resp.data.items.length,
          });
        }

        return resp;
      })
      .catch(() => {});
  }

  fetchEntityList(params) {
    return fetchPaymentPagesList(params)
      .then(resp => {
        if (resp.data) {
          this.props.populateRPLReduxList(resp);
        }

        this.setState({ loading: false });

        return resp;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.setState({ loading: false });
      });
  }

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      trackListActions('Search', label);
    }
  };

  onClearAnalytics = () => {
    trackListActions('Clear');
  };

  render() {
    const { loading, loadingAllList, totalPaymentPagesLength } = this.state;
    const { paymentPages, user } = this.props;

    const isRoleAllowedEdit = user.isAllowedEdit('payment_pages');

    let content;

    if (loadingAllList) {
      content = (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    } else if (
      isRoleAllowedEdit &&
      !loadingAllList &&
      !totalPaymentPagesLength &&
      !paymentPages.length
    ) {
      // !paymentPages check is required so that while creation first time, the list would be updated while totalPaymentPagesLength still = 0
      content = <OnboardingPP />;
    } else {
      content = (
        <React.Fragment>
          <ListFilter
            form="PaymentPagesPaymentListFilter"
            count={this.state.count}
            onSearchAnalytics={this.onSearchAnalytics}
            onClearAnalytics={this.onClearAnalytics}
          >
            <div class="form-group list-filter-item">
              <label>Title</label>
              <Field
                name="title"
                component="input"
                class="form-control input-sm"
              />
            </div>

            <div class="form-group list-filter-item">
              <label>Status</label>
              <Field
                name="status"
                component="select"
                class="form-control input-sm"
              >
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </Field>
            </div>

            <div class="form-group list-filter-item count">
              <label>Count</label>
              <Field
                name="count"
                component="input"
                min={1}
                max={100}
                type="number"
                class="form-control input-sm"
              />
            </div>
          </ListFilter>
          {this.props.user.isPPV3Enabled ? (
            <PaymentPagesV3List loading={loading} paymentPages={paymentPages} />
          ) : (
            <PaymentPagesV2List loading={loading} paymentPages={paymentPages} />
          )}
          {!loading &&
            !!paymentPages.length && (
              <Pager
                count={this.state.count}
                skip={this.state.skip}
                length={paymentPages.length}
                onClick={this.paginate}
              />
            )}
        </React.Fragment>
      );
    }

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <ShowWhen
              additionalCondition={user =>
                user.isOrgAllowedFunctionality('external_links')
              }
            >
              <a
                class="btn btn-link settlement-doc-btn"
                href="https://razorpay.com/docs/payment-pages/"
                target="_blank"
              >
                Documentation&nbsp;<span class="icon i-external-link" />
              </a>
            </ShowWhen>

            {isRoleAllowedEdit && (
              <NavLink class="btn btn-primary" to="/paymentpages/new">
                <i class="i i-plus" />
                <span>Create Payment Page</span>
              </NavLink>
            )}
          </div>
        </HeaderAction>

        {content}
      </div>
    );
  }
}
