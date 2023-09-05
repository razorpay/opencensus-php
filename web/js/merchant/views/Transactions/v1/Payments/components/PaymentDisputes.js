import React from 'react';
import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import Time from 'common/ui/Time';
import { DisputeStatusLabel as StatusLabel } from 'merchant/components/StatusLabel';

const PaymentDisputes = ({ disputes, onDisputeClick }) => {
  const showContent = Boolean(disputes?.filter((d) => d.status === 'open').length);
  return (
    <ContentToggler show={showContent}>
      <span>{disputes.length} Disputes raised</span>
      {disputes.map((dispute) => (
        <Definition allowEmptyTitle={true} key={dispute.id} customClass="m-t payment-dispute">
          <a onClick={() => onDisputeClick(`disputes/${dispute.id}`)}>
            <code>{dispute.id}</code>
          </a>
          <div class="m-t">
            <Amount value={dispute.amount} currency={dispute.currency} className="p-r m-r" />
            <StatusLabel status={dispute.status} />
          </div>
          {dispute.status === 'open' ? (
            <div class="alert alert-warning banner">
              <p>
                Your customer has raised a {dispute.phase} on this payment. To avoid losing the
                dispute&nbsp;
                <u>
                  respond before <Time value={dispute.respond_by} format="ll" />
                </u>
              </p>
            </div>
          ) : (
            ''
          )}
        </Definition>
      ))}
    </ContentToggler>
  );
};

export default PaymentDisputes;
