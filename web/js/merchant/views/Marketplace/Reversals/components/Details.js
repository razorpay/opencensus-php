import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Amount from 'common/ui/Amount';
import Definition from 'common/ui/Definition';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import SettlementInfo from 'merchant/views/Settlements/components/SettlementInfo';

class ReversalDetails extends Component {
  render() {
    const { reversal, transfer, isLoading, onClose, merchant } = this.props;
    const isLAInitiator = (reversal.initiator_id || '').replace('acc_', '') !== merchant.id;

    return (
      <div className="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div className="panel panel-default SliderPanel">
            <div className="panel-heading">
              {onClose && (
                <button type="button" className="close close-secondary" onClick={onClose}>
                  <i className="i i-arrow-back" />
                  <i className="i i-close" />
                </button>
              )}
              Reversal ID: <strong>{reversal.id}</strong>
            </div>

            <div className="SliderPanel__Body">
              <div className="panel-body">
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
                          <div className="m-b" key={index}>
                            <Definition>
                              {key}
                              {String(reversal.notes[key])}
                              {!!reversal.linked_account_notes &&
                                reversal.linked_account_notes.indexOf(key) > -1 && (
                                  <span>
                                    <i className="i i-info-outline" /> This note is shown to the
                                    linked account
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

export default connect((state) => {
  return {
    user: state.session.user,
  };
}, null)(ReversalDetails);
