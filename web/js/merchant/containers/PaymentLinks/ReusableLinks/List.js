import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import { NavLink } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';
import ShowWhen from 'merchant/components/ShowWhen';
import ListContainer from 'merchant/containers/ListContainer';
import ListFilter from 'merchant/components/ListFilter';
import { fetchReusableLinksList } from 'merchant/containers/PaymentLinks/ReusableLinks/model';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'rzp/ui/Amount';
import TableBody from 'rzp/ui/TableBody';
import { PaymentLinkStatusLabel } from 'merchant/components/StatusLabel';
import Time from 'rzp/ui/Time';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { showNotification } from 'rzp/modules/notifications';

@connect(null, { showNotification })
export default class PLResuableContianer extends ListContainer {
  state = {
    paymentLinks: [],
    loading: true,
  };

  fetchEntityList(params) {
    return fetchReusableLinksList(params)
      .then(response => {
        if (response.data) {
          this.setState({ paymentLinks: response.data.items });
        }
        this.setState({ loading: false });
        return response;
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
        eventCategory: 'Dashboard - Payment Links',
        eventAction: 'Search - Payment Links',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payment Links',
      eventAction: 'Clear Search Params - Payment Links',
    });
  };

  onCopy = ({ paymentLinkId }) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payment Links',
      eventAction: 'Copy - Payment Link',
      eventLabel: `payment_link_id=${paymentLinkId}`,
    });
  };

  render() {
    let { paymentLinks, loading } = this.state;

    return (
      <div class="content-wrapper">
        <TestModeBanner />

        <HeaderAction>
          <ShowWhen notMyRole="support">
            <div class="btn-toolbar pull-right">
              <NavLink class="btn btn-primary" to="/paymentlinks/reusable/new">
                <i class="i i-plus" />
                <span>Create Payment Link</span>
              </NavLink>
            </div>
          </ShowWhen>
        </HeaderAction>

        <ListFilter
          form="ReusablePaymentLinkListFilter"
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

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Title</th>
                <th class="text-right">Amount</th>
                <th>Times Payable</th>
                <th>Link Url</th>
                <th>Created At</th>
                <th>Status</th>
              </tr>
            </thead>
            <TableBody
              isLoading={loading}
              colSpan={8}
              rows={paymentLinks}
              emptyTableMsg="No data found!"
            >
              {paymentLinks.map(paymentLink => (
                <EntityItemRow id={paymentLink.id} key={paymentLink.id}>
                  <td>
                    <NavLink to={`/paymentlinks/reusable/${paymentLink.id}`}>
                      <code>{paymentLink.title}</code>
                    </NavLink>
                  </td>
                  <td class="text-right">
                    <Amount
                      value={paymentLink.amount}
                      currency={paymentLink.currency}
                    />
                  </td>
                  <td>{paymentLink.times_payable}</td>
                  <td>
                    {paymentLink.short_url && (
                      <span class="CopyLink">
                        <span>{paymentLink.short_url}</span>
                        <CustomClipboard
                          value={paymentLink.short_url}
                          onCopy={this.onCopy({
                            paymentLinkId: paymentLink.id,
                          })}
                        >
                          <button class="btn btn-default btn-xs">copy</button>
                        </CustomClipboard>
                      </span>
                    )}
                  </td>
                  <td>
                    <Time value={paymentLink.created_at} />
                  </td>
                  <td>
                    <PaymentLinkStatusLabel status={paymentLink.status} />
                  </td>
                </EntityItemRow>
              ))}
            </TableBody>
          </table>
        </div>

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={paymentLinks.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
