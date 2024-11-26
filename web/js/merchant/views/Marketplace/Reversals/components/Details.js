import { Component } from 'react';
import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Definition from 'common/ui/Definition';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import SettlementInfo from 'merchant/views/Settlements/components/SettlementInfo';
import { connect } from 'react-redux';

@connect((state) => {
  return {
    user: state.session.user,
  };
}, null)
export default class ReversalDetails extends Component {
  render() {
    const { reversal, transfer, isLoading, onClose, merchant } = this.props;
    const isLAInitiator = (reversal.initiator_id || '').replace('acc_', '') !== merchant.id;

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
                <button type="button" class="close close-secondary" onClick={onClose}>
                  <i class="i i-arrow-back" />
                  <i class="i i-close" />
                </button>
              )}
              Reversal ID: <strong>{reversal.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                {transfer.recipient_details && (
                  <EntityDetailRow label="Linked Account">
                    <Definition>
                      <span>{transfer.recipient_details.name}</span>
                      {transfer.recipient_details.email && (
                        <span>{transfer.recipient_details.email}</span>
                      )}
                      <code>{transfer.recipient}</code>
                    </Definition>
                  </EntityDetailRow>
                )}

                <EntityDetailRow label="Amount">
                  <Amount value={reversal.amount} currency={reversal.currency} />
                </EntityDetailRow>

                <EntityDetailRow
                  label="Initiated By"
                  value={() => (isLAInitiator ? transfer.recipient_details?.name : merchant.name)}
                />

                {isLAInitiator && reversal.customer_refund_id && (
                  <EntityDetailRow
                    label="Customer Refund ID"
                    value={() => reversal.customer_refund_id}
                  />
                )}

                <EntityDetailRow
                  label="Created At"
                  value={() => (
                    <Time value={reversal.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                  )}
                />

                {reversal.transaction && (
                  <EntityDetailRow label="Settlement Details">
                    <SettlementInfo data={reversal} page="Reversal Detail" />
                  </EntityDetailRow>
                )}

                <EntityDetailRow
                  label="Source ID"
                  value={() => (
                    <div>
                      <Link to={`/route/transfers/${reversal.transfer_id}`}>
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
                          <div class="m-b" key={index}>
                            <Definition>
                              {key}
                              {String(reversal.notes[key])}
                              {!!reversal.linked_account_notes &&
                                reversal.linked_account_notes.indexOf(key) > -1 && (
                                  <span>
                                    <i class="i i-info-outline" /> This note is shown to the linked
                                    account
                                  </span>
                                )}
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
