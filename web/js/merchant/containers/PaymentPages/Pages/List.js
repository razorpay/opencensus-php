import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import { NavLink } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';
import ShowWhen from 'merchant/components/ShowWhen';
import ListContainer from 'merchant/containers/ListContainer';
import ListFilter from 'merchant/components/ListFilter';
import { fetchPaymentPagesList } from './model';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'rzp/ui/Amount';
import TableBody from 'rzp/ui/TableBody';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import Time from 'rzp/ui/Time';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { showNotification } from 'rzp/modules/notifications';

import { populateRPLReduxList } from 'merchant/modules/invoices/list';

import EarlyAccessRPL from './EarlyAccess';

@connect(state => ({ ...state.invoices, ...state.session }), {
  showNotification,
  populateRPLReduxList,
})
export default class PaymentPagesContainer extends ListContainer {
  state = {
    loading: true,
  };

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
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Payment Pages',
        eventAction: 'Search - Payment Pages',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payment Pages',
      eventAction: 'Clear Search Params - Payment Pages',
    });
  };

  onCopy = ({ paymentLinkId }) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payment Pages',
      eventAction: 'Copy - Payment Page Link',
      eventLabel: `payment_link_id=${paymentLinkId}`,
    });
  };

  render() {
    const { loading } = this.state;
    const { paymentPages } = this.props;

    if (false) {
      return (
        <div class="content-wrapper">
          <EarlyAccessRPL />
        </div>
      );
    }

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <ShowWhen notMyRole="support">
            <div class="btn-toolbar pull-right">
              <NavLink class="btn btn-primary" to="/paymentpages/new">
                <i class="i i-plus" />
                <span>Create Payment Page</span>
              </NavLink>
            </div>
          </ShowWhen>
        </HeaderAction>

        <ListFilter
          form="PaymentPagesPaymentLitFilter"
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
            <label>Receipt No.</label>
            <Field
              name="receipt"
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

          <div class="form-group list-filter-item">
            <label>Notes</label>
            <Field
              name="notes"
              component="input"
              class="form-control input-sm"
            />
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

        {loading || paymentPages.length ? (
          <div class="table-responsive">
            <table class="table table-hover table-striped">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Amount</th>
                  <th>Payments Made</th>
                  <th>Times Payable</th>
                  <th>Total Sales</th>
                  <th>Link Url</th>
                  <th>Created At</th>
                  <th>Status</th>
                </tr>
              </thead>
              <TableBody
                isLoading={loading}
                colSpan={8}
                rows={paymentPages}
                emptyTableMsg="No data found!"
              >
                {paymentPages.map(item => (
                  <EntityItemRow id={item.id} key={item.id}>
                    <td>
                      <NavLink to={`/paymentpages/${item.id}`}>
                        <code>{item.title}</code>
                      </NavLink>
                    </td>
                    <td>
                      <Amount value={item.amount} currency={item.currency} />
                    </td>
                    <td>{item.times_paid}</td>
                    <td>{item.times_payable || '--'}</td>
                    <td>
                      <Amount
                        value={item.total_amount_paid}
                        currency={item.currency}
                      />
                    </td>
                    <td>
                      {item.short_url && (
                        <span class="CopyLink">
                          <span>{item.short_url}</span>
                          <CustomClipboard
                            value={item.short_url}
                            onCopy={this.onCopy({
                              itemId: item.id,
                            })}
                          >
                            <button class="btn btn-default btn-xs">copy</button>
                          </CustomClipboard>
                        </span>
                      )}
                    </td>
                    <td>
                      <Time value={item.created_at} />
                    </td>
                    <td>
                      <PaymentPagesStatusLabel status={item.status} />
                    </td>
                  </EntityItemRow>
                ))}
              </TableBody>
            </table>
          </div>
        ) : (
          <div class="Onboarding Onboarding--PaymentPages">
            <div class="illustration" />
            You haven't created any payment page yet.{' '}
            <a
              class="btn-link"
              href="https://razorpay.com/docs/private/partial-payments/"
              target="_blank"
            >
              Learn more
            </a>
            <br />
            <NavLink class="btn btn-primary" to="/paymentpages/new">
              <i class="i i-plus" />
              <span>Create your first Payment Page</span>
            </NavLink>
          </div>
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
      </div>
    );
  }
}
