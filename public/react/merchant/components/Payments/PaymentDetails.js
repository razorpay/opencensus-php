import { Link } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import Banner from 'rzp/ui/Banner';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import CheckIcon from 'rzp/ui/CheckIcon';
import Alert from 'rzp/ui/Forms/Alert';
import DataTable from 'rzp/ui/Table/DataTable';
import { titleCase } from 'rzp/utils/rzp-utils';
import ListToggler from 'rzp/ui/Toggler/ListToggler';
import ListGroupToggler from 'rzp/ui/Toggler/ListGroupToggler';
import Definition from 'rzp/ui/Definition';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import ShowWhen from 'merchant/components/ShowWhen';
import OtherDetail from 'merchant/components/OtherDetail';
import { refundId, amount, createdAt } from 'rzp/ui/item/pair';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import PaymentMethod from 'merchant/components/Payments/PaymentMethod';
import PaymentRefund from 'merchant/components/Payments/PaymentRefund';
import PaymentTransfers from 'merchant/components/Payments/PaymentTransfers.js';

export default props => {
  let {
    payment,
    card,
    bankTransfer, //virtual account details
    refunds,
    transfers,
    isLoading,
    openRefundModal,
    statusMsg = {},
  } = props;

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            {props.onClose && (
              <button
                type="button"
                class="close close-secondary"
                onClick={props.onClose}
              >
                <i class="icon icon-close" />
              </button>
            )}
            Payment Id: <b>{payment.id}</b>
          </div>

          <div class="SliderPanel__Body">
            <ShowWhen myRole="owner manager operations admin">
              {payment.status === 'authorized' && (
                <Banner
                  cta="Capture Payment"
                  ctaOnClick={() => {
                    props.confirmCapture(payment);
                  }}
                >
                  <span>
                    This payment will be auto-refunded if not captured within 5
                    days of creation.
                  </span>
                </Banner>
              )}
            </ShowWhen>

            <div class="panel-body">
              <Alert type={statusMsg.type} message={statusMsg.message} />
              <div class="list-group pair-row-container">
                <EntityDetailRow label="Amount">
                  <b>
                    <Amount
                      value={payment.amount}
                      currency={payment.currency}
                    />
                  </b>
                </EntityDetailRow>

                <EntityDetailRow label="Status">
                  <PaymentStatusLabel status={payment.status} />
                </EntityDetailRow>

                {payment.error_code && (
                  <EntityDetailRow label="Error">
                    <Definition>
                      <span>{payment.error_code}</span>
                      {payment.error_description && (
                        <span>{payment.error_description}</span>
                      )}
                    </Definition>
                  </EntityDetailRow>
                )}

                <ShowWhen apiFeatureEnabled="Marketplace">
                  <EntityDetailRow label="Transfer">
                    <PaymentTransfers
                      payment={payment}
                      transfers={transfers}
                      onCreateTransfer={props.goToLink}
                    />
                  </EntityDetailRow>
                </ShowWhen>

                <EntityDetailRow label="Refunds">
                  <PaymentRefund
                    payment={payment}
                    refunds={refunds}
                    openRefundModal={openRefundModal}
                  />
                </EntityDetailRow>

                <EntityDetailRow label="Payment Method">
                  <PaymentMethod
                    payment={payment}
                    card={card}
                    bankTransfer={bankTransfer}
                  />
                </EntityDetailRow>

                <EntityDetailRow label="Created At">
                  <Time
                    value={payment.created_at}
                    format="DD MMM YYYY, hh:mm:ss a"
                  />
                </EntityDetailRow>

                <EntityDetailRow label="Description">
                  {payment.description}
                </EntityDetailRow>

                <EntityDetailRow label="Customer">
                  <Definition placeholder="No customer linked">
                    {payment.email}
                    {payment.contact}
                  </Definition>
                </EntityDetailRow>

                <EntityDetailRow label="Total Fee">
                  <Definition>
                    <Amount value={payment.fee} />
                    <span>
                      Razorpay Fee -{' '}
                      <Amount value={payment.fee - payment.tax} />
                    </span>
                    <span>
                      GST - <Amount value={payment.tax} />
                    </span>
                  </Definition>
                </EntityDetailRow>

                <EntityDetailRow label="Order ID">
                  {payment.order_id ? (
                    <Link to={`/orders/${payment.order_id}`}>
                      <code>{payment.order_id}</code>
                    </Link>
                  ) : (
                    '--'
                  )}
                </EntityDetailRow>

                <EntityDetailRow label="Invoice ID">
                  {payment.invoice_id ? (
                    <Link to={`/invoices/${payment.invoice_id}`}>
                      <code>{payment.invoice_id}</code>
                    </Link>
                  ) : (
                    '--'
                  )}
                </EntityDetailRow>

                <EntityDetailRow label="Notes">
                  {Object.keys(payment.notes).length
                    ? Object.keys(payment.notes).map((key, index) => (
                        <Definition key={index} customClass="notes">
                          {key}
                          {String(payment.notes[key] || '--')}
                        </Definition>
                      ))
                    : '--'}
                </EntityDetailRow>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
