import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Amount from 'common/ui/Amount';
import Banner from 'common/ui/Banner';
import Button from 'common/new-ui/Button';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import Definition from 'common/ui/Definition';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Table from 'common/ui/Table/Index';
import { paymentId, amount } from 'common/ui/item/pair';
import { showNotification } from 'merchant_common/reducers/notifications';
import { QRCodeStatusLabel } from 'merchant/components/StatusLabel';
import CustomerDetails from 'merchant/components/CustomerDetails';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { findBy } from 'common/utils/rzp-utils';

export default function Details(props) {
  const {
    qrCode,
    payments,
    mode,
    isLoading,
    statusMsg,
    onClose,
    onMakeTestPaymentClick,
    user,
    customers,
    isTestMode,
    showPreview,
    downloadQRCode,
  } = props;

  const isClosed = qrCode.status === 'closed';

  const showTestPaymentBtn = isTestMode && qrCode.status === 'active';

  const customerDetails = findBy(customers.items, 'id', qrCode.customer_id) || {};

  return (
    <div class="content-wrapper content-sm txn-details QRCode--Details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-qr-code text-warm" /> <strong>{qrCode.id}</strong>
          </div>

          <div class="SliderPanel__Body">
            <Alert type={statusMsg.type} message={statusMsg.message} />

            <div class="info">
              <div>
                <div class="heading">Amount Received</div>
                <div class="value">
                  <Amount value={qrCode.total_amount_received || '000'} />
                </div>
              </div>
              <div>
                <div class="heading">Number of Payments</div>
                <div class="value">{qrCode.payments_received_count || 0}</div>
              </div>
            </div>

            <div class="actions">
              <Button.Transparent class="Button--Link" onClick={showPreview}>
                <i class="i i-eye m-r" /> Preview QR
              </Button.Transparent>
              <Button.Transparent class="Button--Link" onClick={downloadQRCode}>
                <i class="i i-download m-r" /> Download QR
              </Button.Transparent>
            </div>

            <div class="panel-body">
              <div>
                <EntityDetailRow label="Created At">
                  <Time value={qrCode.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                </EntityDetailRow>

                <EntityDetailRow label="Status">
                  <div class="status">
                    <QRCodeStatusLabel status={qrCode.status} />

                    {!isClosed && (
                      <button class="btn btn-link" onClick={onClose}>
                        Close
                      </button>
                    )}
                  </div>
                </EntityDetailRow>

                <EntityDetailRow
                  label="QR Usage"
                  pairClass="qr-usage"
                  value={(qrCode.usage || '').replace('_', ' ')}
                />

                <EntityDetailRow label="QR Name" value={qrCode.name} />

                <EntityDetailRow label="Payment Amount">
                  {qrCode.payment_amount ? (
                    <>
                      <Amount value={qrCode.payment_amount} />
                      <div>QR code only accepts payments of this amount.</div>
                    </>
                  ) : (
                    <>Accepts payments of any amount.</>
                  )}
                </EntityDetailRow>

                <EntityDetailRow label="Close By" value={qrCode.close_by} />

                <EntityDetailRow label="Customer Details">
                  <CustomerDetails
                    isLoading={customers.loading}
                    customerId={customerDetails.id}
                    name={customerDetails.name}
                    email={customerDetails.email}
                    contact={customerDetails.contact}
                  />
                </EntityDetailRow>

                <EntityDetailRow label="Description" value={qrCode.description} />

                <NestedEntityDetailRow label="Notes" value={qrCode.notes} />
              </div>

              {showTestPaymentBtn &&
                {
                  /* <Banner class="QRCode-test-payment">
                  <Button onClick={onMakeTestPaymentClick}>Make a Test Payment</Button>

                  <div>
                    <strong>Test Mode:</strong> Make a test payment using this QR Code
                  </div>
                </Banner> */
                }}

              <hr />

              <div>
                <p class="text-muted" style={{ lineHeight: '35px' }}>
                  Recent Payments
                  <Link class="pull-right" to={`/qr_codes/payments/?qr_code_id=${qrCode.id}`}>
                    View All Payments
                  </Link>
                </p>

                <Table rows={payments} columns={[paymentId, amount]} showHeaders={false} />
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
