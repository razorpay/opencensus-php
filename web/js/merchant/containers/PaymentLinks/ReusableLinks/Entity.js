import React, { Component } from 'react';
import { NavLink } from 'react-router-dom';
import { connect } from 'react-redux';
import {
  fetchReusableLinksEntity,
  fetchReusableLinkPaymentsList,
} from './model';
import { ReusableLinksStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import StatsInfo from 'ui/StatsTable';
import GroupDetailsTable from 'rzp/ui/GroupDetailsTable';

import { showNotification } from 'rzp/modules/notifications';

@connect(null, { showNotification })
export default class ReusableLinksEntity extends Component {
  state = {
    reusableLink: {},
    loading: true,
    reusableLinkPayments: [],
    paymentsListLoading: true,
  };

  componentWillMount() {
    this.fetchEntity(this.props.id);
    this.fetchEntityPayments(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchEntity(nextProps.id);
      this.fetchEntityPayments(this.props.id);
    }
  }

  fetchEntity(id) {
    return fetchReusableLinksEntity(id)
      .then(resp => {
        if (resp) {
          this.setState({ reusableLink: resp.data });
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

  getStatsTable(reusableLink) {
    return [
      [
        { title: 'Payments Made', value: reusableLink.times_paid },
        {
          title: 'Total Sales',
          value: (
            <Amount
              value={reusableLink.total_amount_paid}
              currency={reusableLink.currency}
            />
          ),
        },
      ],
    ];
  }

  fetchEntityPayments(id) {
    return fetchReusableLinkPaymentsList(id)
      .then(resp => {
        if (resp) {
          this.setState({ reusableLinkPayments: resp.data.items });
        }

        this.setState({ paymentsListLoading: false });

        return resp;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.setState({ paymentsListLoading: false });
      });
  }

  onCopy = ({ reusableLinkId }) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Reusable Payment Links',
      eventAction: 'Copy - Reusable Payment Link',
      eventLabel: `payment_link_id=${reusableLinkId}`,
    });
  };

  render() {
    let {
      reusableLink,
      loading,
      reusableLinkPayments,
      paymentsListLoading,
    } = this.state;
    let isExpired = reusableLink.status_reason === 'expired';

    return (
      <div class="content-wrapper content-sm txn-details Entity--reusable">
        {loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="i i-link text-primary icon--formal" />{' '}
              <strong>{reusableLink.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
                  <StatsInfo stats={this.getStatsTable(reusableLink)} />
                  <EntityDetailRow
                    label="Amount"
                    value={() => (
                      <Amount
                        value={reusableLink.amount}
                        currency={reusableLink.currency}
                      />
                    )}
                  />
                  <EntityDetailRow
                    label="Link URL"
                    value={() => (
                      <span class="CopyLink">
                        <span>{reusableLink.short_url}</span>
                        <CustomClipboard
                          value={reusableLink.short_url}
                          onCopy={this.onCopy({
                            reusableLinkId: reusableLink.id,
                          })}
                        >
                          <button class="btn btn-default btn-xs">copy</button>
                        </CustomClipboard>
                      </span>
                    )}
                  />
                  <EntityDetailRow
                    label="Status"
                    value={() => (
                      <ReusableLinksStatusLabel status={reusableLink.status} />
                    )}
                  />
                  <EntityDetailRow
                    label="Payment For"
                    pairClass="description"
                    value={() => (
                      <div>
                        {reusableLink.title}
                        {reusableLink.description && (
                          <div class="label--secondary">
                            {reusableLink.description}
                          </div>
                        )}
                      </div>
                    )}
                  />
                  <EntityDetailRow label="Created by">
                    {!!reusableLink.user ? (
                      <Definition>
                        {reusableLink.user.name}
                        {reusableLink.user.email}
                      </Definition>
                    ) : (
                      'API'
                    )}
                  </EntityDetailRow>

                  <EntityDetailRow
                    label="Created At"
                    value={() => <Time value={reusableLink.created_at} />}
                  />
                  <EntityDetailRow
                    label={isExpired ? 'Expired on' : 'Expires on'}
                    value={() => (
                      <Time
                        value={reusableLink.expire_by}
                        format="DD MMM YYYY, hh:mm a"
                      />
                    )}
                  />
                  <EntityDetailRow
                    label="Times Payable"
                    value={reusableLink.times_payable}
                  />
                  <NestedEntityDetailRow
                    label="Notes"
                    value={reusableLink.notes}
                  />

                  <GroupDetailsTable
                    title="Successful Payments"
                    subTitle={
                      <React.Fragment>
                        <Amount
                          value={reusableLink.total_amount_paid}
                          currency={reusableLink.currency}
                        />{' '}
                        total sales
                      </React.Fragment>
                    }
                    class="reusable-link-payments-table"
                    loading={paymentsListLoading}
                    items={reusableLinkPayments}
                    rowConfig={[
                      [
                        data => (
                          <span class="label--primary">{data.contact}</span>
                        ),
                        data => (
                          <NavLink
                            class="btn-link no-padding"
                            to={`/payments/${data.id}`}
                            target="_blank"
                          >
                            {data.id}
                          </NavLink>
                        ),
                      ],
                      [
                        data => (
                          <span class="label--secondary">{data.email}</span>
                        ),
                        data => (
                          <span class="label--secondary">
                            <Time
                              value={data.created_at}
                              format="DD MMM YYYY, hh:mm:ss a"
                            />
                          </span>
                        ),
                      ],
                    ]}
                    loaderConfig={[
                      [{ width: '70%' }, { width: '45%' }],
                      [{ width: '60%', height: '10px' }, { width: '30%' }],
                    ]}
                  />
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}
