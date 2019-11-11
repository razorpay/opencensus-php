import { Link } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import Banner from 'rzp/ui/Banner';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import { titleCase } from 'rzp/utils/rzp-utils';
import Definition from 'rzp/ui/Definition';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import ShowWhen from 'merchant/components/ShowWhen';
import { refundId, amount, createdAt } from 'rzp/ui/item/pair';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import PaymentMethod from 'merchant/components/Payments/PaymentMethod';
import PaymentRefund from 'merchant/components/Payments/PaymentRefund';
import PaymentTransfers from 'merchant/components/Payments/PaymentTransfers.js';
import PaymentDisputes from './PaymentDisputes';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';

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
    onRefundDetailsToggleClick = () => {},
    isRoleAllowedEdit,
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
                <i class="i i-close" />
              </button>
            )}
            Payment Id: <b>{payment.id}</b>
          </div>

          <div class="SliderPanel__Body">
            {payment.status === 'authorized' && (
              <Banner
                cta={isRoleAllowedEdit ? 'Capture Payment' : ''}
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
                    onToggleClick={onRefundDetailsToggleClick}
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

                <EntityDetailRow label="Disputes">
                  {payment.disputes && payment.disputes.count ? (
                    <PaymentDisputes disputes={payment.disputes.items} />
                  ) : (
                    '--'
                  )}
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
                      <Amount
                        value={payment.fee - payment.tax}
                        currency={'INR'}
                      />
                    </span>
                    <span>
                      GST - <Amount value={payment.tax} currency={'INR'} />
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

                {payment.invoice_id && (
                  <EntityDetailRow label="Invoice ID">
                    <Link to={`/invoices/${payment.invoice_id}`}>
                      <code>{payment.invoice_id}</code>
                    </Link>
                  </EntityDetailRow>
                )}

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

                <EntityDetailRow label="Settlement Details">
                  <ContentToggler>
                    <span>Setted on 29 June 2019</span>
                    <div>
                      <div>setl_7dqkj1nBSEZqo</div>
                      <div class="settlement-detail-row">
                        <span>Settlement Amount</span>
                        <span>10,745 INR</span>
                      </div>
                      <div class="settlement-detail-row">
                        <span>Total Fee</span>
                        <span>260 INR</span>
                      </div>
                      <div class="settlement-detail-row settlement-shift-row-right">
                        <span>Razorpay Fee</span>
                        <span>220 INR</span>
                      </div>
                      <div class="settlement-detail-row settlement-shift-row-right">
                        <span>GST(18%) Fee</span>
                        <span>40 INR</span>
                      </div>
                    </div>
                  </ContentToggler>
                </EntityDetailRow>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
