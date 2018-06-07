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
import { showNotification } from 'rzp/modules/notifications';
import { classList } from 'common/util';
import StatsInfo from 'ui/StatsTable';

import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

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
                    label="Description"
                    pairClass="description"
                    value={() => (
                      <div>
                        {reusableLink.title}
                        {reusableLink.description && (
                          <div class="label--secondary">
                            reusableLink.description
                          </div>
                        )}
                      </div>
                    )}
                  />
                  <EntityDetailRow
                    label="Created At"
                    value={() => <Time value={reusableLink.date} />}
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

                  <PaymentDetailsTable
                    title="Successful Payments"
                    subTitle={`${reusableLink.total_amount_paid} total sales`}
                    class="reusable-link-payments-table"
                    items={reusableLinkPayments}
                    loading={paymentsListLoading}
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

const PaymentDetailsTable = ({
  title,
  subTitle,
  className,
  loading,
  items,
}) => {
  return (
    <div class={classList('entity-detail-list', className)}>
      <div class="list-heading">
        <span class="label--primary">
          <b>{title}</b>
        </span>
        <span class="label--secondary">{subTitle}</span>
      </div>
      {items.map((rowData, idx) => (
        <PaymentDetailsRow key={idx} rowData={rowData} loading={loading} />
      ))}
    </div>
  );
};

const PaymentDetailsRow = ({ loading, rowData }) => {
  return (
    <div class="entity-detail-row">
      <div class="row-item content">
        <div class="detail-row">
          <div class="row-element left">
            {loading ? (
              <PlaceholderLoader style={{ width: '70%' }} />
            ) : (
              <span class="label--primary">{rowData.contact}</span>
            )}
          </div>
          <div class="row-element right">
            {loading ? (
              <PlaceholderLoader style={{ width: '45%' }} />
            ) : (
              <NavLink
                class="btn-link no-padding"
                to={`/payments/${rowData.id}`}
                target="_blank"
              >
                {rowData.id}
              </NavLink>
            )}
          </div>
        </div>

        <div class="detail-row">
          <div class="row-element left">
            {loading ? (
              <PlaceholderLoader style={{ width: '60%', height: '10px' }} />
            ) : (
              <span class="label--secondary">{rowData.email}</span>
            )}
          </div>
          <div class="row-element right">
            {loading ? (
              <PlaceholderLoader style={{ width: '30%' }} />
            ) : (
              <span class="label--secondary">
                <Time
                  value={rowData.created_at}
                  format="DD MMM YYYY, hh:mm:ss a"
                />
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
