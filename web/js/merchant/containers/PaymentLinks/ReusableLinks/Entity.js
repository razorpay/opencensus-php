import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchReusableLinksEntity } from './model';
import { ReusableLinksStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import { showNotification } from 'rzp/modules/notifications';

@connect(null, { showNotification })
export default class ReusableLinksEntity extends Component {
  state = {
    reusableLink: {},
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

  onCopy = ({ reusableLinkId }) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Reusable Payment Links',
      eventAction: 'Copy - Reusable Payment Link',
      eventLabel: `payment_link_id=${reusableLinkId}`,
    });
  };

  render() {
    let { reusableLink, loading } = this.state;
    let isExpired = reusableLink.status_reason === 'expired';

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
              <strong>{reusableLink.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
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
                    value={reusableLink.description || '--'}
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
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}
