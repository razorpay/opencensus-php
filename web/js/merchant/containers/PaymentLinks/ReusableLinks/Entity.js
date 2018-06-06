import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchReusableLinksEntity } from './model';
import { PaymentLinkStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { showNotification } from 'rzp/modules/notifications';

@connect(null, { showNotification })
export default class ReusableLinkEntity extends Component {
  state = {
    paymentLink: {},
    loading: true,
  };

  componentWillMount() {
    this.fetchEntity(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchEntity(nextProps.id);
    }
  }

  fetchEntity(id) {
    return fetchReusableLinksEntity(id)
      .then(response => {
        if (response) {
          this.setState({ paymentLink: response.data });
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

  onCopy = ({ paymentLinkId }) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payment Links',
      eventAction: 'Copy - Payment Link',
      eventLabel: `payment_link_id=${paymentLinkId}`,
    });
  };

  render() {
    let { paymentLink, loading } = this.state;
    let isExpired = paymentLink.status_reason === 'expired';

    return (
      <div class="content-wrapper content-sm txn-details">
        {loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="i i-link text-primary icon--formal" />{' '}
              <strong>{paymentLink.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
                  <EntityDetailRow
                    label="Amount"
                    value={() => (
                      <Amount
                        value={paymentLink.amount}
                        currency={paymentLink.currency}
                      />
                    )}
                  />
                  <EntityDetailRow
                    label="Link URL"
                    value={() => (
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
                  />
                  <EntityDetailRow
                    label="Status"
                    value={() => (
                      <PaymentLinkStatusLabel status={paymentLink.status} />
                    )}
                  />
                  <EntityDetailRow
                    label="Description"
                    pairClass="description"
                    value={paymentLink.description || '--'}
                  />
                  <EntityDetailRow
                    label="Created At"
                    value={() => <Time value={paymentLink.date} />}
                  />
                  <EntityDetailRow
                    label={isExpired ? 'Expired on' : 'Expires on'}
                    value={() => (
                      <Time
                        value={paymentLink.expire_by}
                        format="DD MMM YYYY, hh:mm a"
                      />
                    )}
                  />
                  <EntityDetailRow
                    label="Times Payable"
                    value={paymentLink.times_payable}
                  />
                  <NestedEntityDetailRow
                    label="Notes"
                    value={paymentLink.notes}
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
