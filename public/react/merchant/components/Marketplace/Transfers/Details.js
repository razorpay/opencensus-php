import { Component } from 'react';
import { Link } from 'react-router-dom';
import { reduxForm } from 'redux-form';

import Alert from 'rzp/ui/Forms/Alert';
import Amount from 'rzp/ui/Amount';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Definition from 'rzp/ui/Definition';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Fee from 'merchant/components/Fee';
import TransferReversal from 'merchant/components/Marketplace/Transfers/TransferReversal';
import SettlementSchedule from 'merchant/components/Marketplace/Transfers/SettlementSchedule';

@reduxForm({
  form: 'settlementSchedule',
})
export default class TransferDetails extends Component {
  render() {
    const {
      transfer,
      isLoading,
      statusMsg,
      openReversalModal,
      reversals,
      onClose,
    } = this.props;

    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading
          ? <div class="page-spinner-container">
              <Spinner />
            </div>
          : <div class="panel panel-default SliderPanel">
              <div class="panel-heading">
                {onClose &&
                  <button
                    type="button"
                    class="close close-secondary"
                    onClick={onClose}
                  >
                    <i class="icon icon-arrow-back" />
                    <i class="icon icon-close" />
                  </button>}
                Transfer ID: <strong>{transfer.id}</strong>
              </div>

              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <Alert type={statusMsg.type} message={statusMsg.message} />

                  <EntityDetailRow label="Linked Account">
                    <Definition>
                      <span>
                        {transfer.recipient_details.name}
                      </span>
                      {transfer.recipient_details.email &&
                        <span>
                          {transfer.recipient_details.email}
                        </span>}
                      <code>
                        {transfer.recipient}
                      </code>
                    </Definition>
                  </EntityDetailRow>

                  <EntityDetailRow label="Amount">
                    <ContentToggler>
                      <Amount value={transfer.amount} />
                      <div className="m-t">
                        <Fee
                          totalFee={transfer.fees}
                          rzpFee={transfer.fees - transfer.tax}
                          tax={transfer.tax}
                        />
                      </div>
                    </ContentToggler>
                  </EntityDetailRow>

                  <EntityDetailRow
                    label="Created At"
                    value={() =>
                      <Time
                        value={transfer.created_at}
                        format="DD MMM YYYY, hh:mm:ss a"
                      />}
                  />

                  <EntityDetailRow label="On Hold">
                    <SettlementSchedule transfer={transfer} />
                  </EntityDetailRow>

                  <EntityDetailRow
                    label="Source ID"
                    value={() =>
                      <div>
                        <Link to={`/payments/${transfer.source}`}>
                          {transfer.source}
                        </Link>
                      </div>}
                  />

                  <EntityDetailRow label="Reversal">
                    <TransferReversal
                      transfer={transfer}
                      reversals={reversals}
                      openTransferReversalModal={openReversalModal}
                    />
                  </EntityDetailRow>

                  {/* Notes */}
                  <EntityDetailRow label="Notes">
                    {transfer.notes &&
                      (Object.keys(transfer.notes).length === 0
                        ? '--'
                        : Object.keys(transfer.notes).map((key, index) =>
                            <div className="m-b" key={index}>
                              <Definition>
                                {key}
                                {String(transfer.notes[key])}
                              </Definition>
                            </div>
                          ))}
                  </EntityDetailRow>
                </div>
              </div>
            </div>}
      </div>
    );
  }
}
