import { Component } from 'react';
import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Definition from 'rzp/ui/Definition';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import { titleCase } from 'rzp/utils/rzp-utils';

import EntityDetailRow from 'merchant/components/EntityDetailRow';

export default class ReversalDetails extends Component {
  handleClickSourceId = _ =>
    window.rzpAnalytics({
      eventCategory: 'LA Dashboard - Reversals',
      eventAction: 'Click - Source ID',
    });

  render() {
    const {
        reversal,
        isLoading,
        onClose,
        parentAccountName,
        merchant,
        isRefundsAllowed,
      } = this.props,
      isLAInitiator =
        reversal.initiator_id &&
        reversal.initiator_id.replace('acc_', '') === merchant.id;

    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              {onClose && (
                <button
                  type="button"
                  class="close close-secondary"
                  onClick={onClose}
                >
                  <i class="i i-arrow-back" />
                  <i class="i i-close" />
                </button>
              )}
              Reversal ID: <strong>{reversal.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <EntityDetailRow label="Amount">
                  <Amount
                    value={reversal.amount}
                    currency={reversal.currency}
                  />
                </EntityDetailRow>

                {isRefundsAllowed && (
                  <EntityDetailRow
                    label="Initiated By"
                    value={() =>
                      isLAInitiator ? merchant.billing_label : parentAccountName
                    }
                  />
                )}

                {isRefundsAllowed &&
                  isLAInitiator && (
                    <EntityDetailRow
                      label="Customer Refund ID"
                      value={_ => reversal.customer_refund_id}
                    />
                  )}

                <EntityDetailRow
                  label="Created At"
                  value={() => (
                    <Time
                      value={reversal.created_at}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  )}
                />

                <EntityDetailRow
                  label="Source ID"
                  value={() => (
                    <div>
                      <Link
                        to={`/transfers/${reversal.transfer_id}`}
                        onClick={this.handleClickSourceId}
                      >
                        {reversal.transfer_id}
                      </Link>
                    </div>
                  )}
                />

                {/* Notes */}
                <EntityDetailRow label="Notes">
                  {reversal.notes &&
                    (Object.keys(reversal.notes).length === 0
                      ? '--'
                      : Object.keys(reversal.notes).map((key, index) => (
                          <div className="m-b" key={index}>
                            <Definition>
                              {key}
                              {String(reversal.notes[key])}
                            </Definition>
                          </div>
                        )))}
                </EntityDetailRow>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}
