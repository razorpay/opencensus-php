import { Link } from 'react-router-dom';

import Amount from 'common/ui/Amount';
// import Banner from 'common/ui/Banner';
import Button from 'common/new-ui/Button';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import PlaceHolderLoader from 'common/ui/PlaceholderLoader';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Table from 'common/ui/Table/Index';
import { paymentId, amount } from 'common/ui/item/pair';
import { QRCodeStatusLabel } from 'merchant/components/StatusLabel';
import CustomerDetails from 'merchant/components/CustomerDetails';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { findBy } from 'common/utils/rzp-utils';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';
import { SelfServeActionPages } from 'common/constant/enums';

const _paymentId = () => {
  return {
    title: paymentId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('payment')(
        item,
        SelfServeActionPages.QRcodesQRcodes,
        'qrcode-details',
      );
      return <div>{intermediateElement}</div>;
    },
  };
};

export default function Details(props) {
  const {
    qrCode,
    payments,
    isLoading,
    statusMsg,
    onClose,
    // onMakeTestPaymentClick,
    customers,
    user,
    // isTestMode,
    showPreview,
    downloadQRCode,
    isPaymentsLoading,
    viewAllPayments,
  } = props;

  const isQRClosed = qrCode.status === 'closed';
  const hideQRCloseButton =
    user.isQRCodeDedicatedTerminalEnabled &&
    qrCode.type === 'upi_qr' &&
    qrCode.usage === 'multiple_use';

  // const showTestPaymentBtn = isTestMode && qrCode.status === 'active';

  const customerDetails = findBy(customers.items, 'id', qrCode.customer_id) || {};

  return (
    <div className="content-wrapper content-sm txn-details QRCode--Details">
      {isLoading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            <i className="i i-qr-code text-warm" /> <strong>{qrCode.id}</strong>
          </div>

          <div className="SliderPanel__Body">
            <Alert type={statusMsg.type} message={statusMsg.message} />

            <div className="panel-body">
              <div className="info">
                <div>
                  <div className="heading">Amount Received</div>
                  <div className="value">
                    <Amount value={qrCode.payments_amount_received || '000'} />
                  </div>
                </div>
                <div>
                  <div className="heading">Number of Payments</div>
                  <div className="value">{qrCode.payments_count_received || 0}</div>
                </div>
              </div>

              <div className="actions">
                <Button.Transparent className="Button--Link" onClick={showPreview}>
                  <i className="i i-eye m-r" /> Preview QR
                </Button.Transparent>
                <Button.Transparent className="Button--Link" onClick={downloadQRCode}>
                  <i className="i i-download m-r" /> Download QR
                </Button.Transparent>
              </div>
              <div>
                <EntityDetailRow label="Created At">
                  <Time value={qrCode.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                </EntityDetailRow>

                <EntityDetailRow label="Status">
                  <div className="status">
                    <QRCodeStatusLabel status={qrCode.status} />
                    {!isQRClosed && !hideQRCloseButton && (
                      <button className="btn btn-link" onClick={onClose}>
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
                      <div>Only accepts payments of this amount.</div>
                    </>
                  ) : (
                    <>Accepts payments of any amount.</>
                  )}
                </EntityDetailRow>

                <EntityDetailRow label="Close By">
                  <Time value={qrCode.close_by} format="DD MMM YYYY, hh:mm:ss a" />
                </EntityDetailRow>

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

              {/* showTestPaymentBtn &&
                <Banner className="QRCode-test-payment">
                  <Button onClick={onMakeTestPaymentClick}>Make a Test Payment</Button>

                  <div>
                    <strong>Test Mode:</strong> Make a test payment using this QR Code
                  </div>
                </Banner>
                }*/}

              <hr />

              <div>
                <p className="text-muted" style={{ lineHeight: '35px' }}>
                  Recent Payments
                  <Link
                    className="pull-right"
                    to={`/qr_codes/payments/?qr_code_id=${qrCode.id}`}
                    onClick={viewAllPayments}
                  >
                    View All Payments
                  </Link>
                </p>

                {isPaymentsLoading ? (
                  <>
                    <PlaceHolderLoader />
                    <PlaceHolderLoader />
                  </>
                ) : (
                  <Table rows={payments} columns={[_paymentId(), amount]} showHeaders={false} />
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
