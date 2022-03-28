import EntityDetailRow from 'merchant/components/EntityDetailRow';
import PaymentOptimizerProvider from 'merchant/views/Transactions/Payments/components/PaymentOptimizerProvider';
import SettlementInfo from 'merchant/views/Settlements/components/SettlementInfo';

const INTEGRATED_GATEWAYS = ['payu', 'paytm'];

export const OptimizerDetails = ({ payment, terminalProviders, scrolledToBottom }) => {
  return (
    <div
      className={`optimizer-payment-details ${
        scrolledToBottom ? '' : 'optimizer-payment-details-shadow'
      }`}
    >
      <div className="heading">
        <i className="i i-routing" /> Optimizer details
      </div>
      <EntityDetailRow label="Processed by">
        <PaymentOptimizerProvider
          terminal_id={payment.optimizer_provider}
          settled_by={payment.settled_by}
          terminalProviders={terminalProviders}
          isDetailView={true}
        />
      </EntityDetailRow>
      {payment.transaction && payment.optimizer_provider !== 'Razorpay' && (
        <EntityDetailRow label="Settlement Details">
          <SettlementInfo data={payment} integratedGateways={INTEGRATED_GATEWAYS} />
        </EntityDetailRow>
      )}
    </div>
  );
};
