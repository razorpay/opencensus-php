import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Definition from 'rzp/ui/Definition';
import Spinner from 'rzp/ui/Spinner';
import Banner from 'rzp/ui/Banner';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import ShowWhen from 'merchant/components/ShowWhen';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { Link } from 'react-router-dom';
import DataTable from 'rzp/ui/Table/DataTable';
import { paymentId, amount, paidOn } from 'rzp/ui/item/pair';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Button, { AsyncBtn } from 'component/Button';
import Input from 'component/Input';

import moment from 'moment';
import { dateCalculator, timeCalculator } from 'component/Input/Calendar';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

const getCustomerDetail = invoice => (
  <Definition placeholder="--">
    {invoice.customer_details.customer_name}
    {invoice.customer_details.customer_email && (
      <span>
        {invoice.customer_details.customer_email}
        {invoice.email_status ? (
          <span
            style={{ marginLeft: '10px' }}
            class={`${notificationClassMap[invoice.email_status]}`}
          >
            ({invoice.email_status} mail)
          </span>
        ) : null}
      </span>
    )}
    {invoice.customer_details.customer_contact && (
      <span>
        {invoice.customer_details.customer_contact}
        {invoice.sms_status ? (
          <span
            style={{ marginLeft: '10px' }}
            class={`${notificationClassMap[invoice.sms_status]}`}
          >
            ({invoice.sms_status} sms)
          </span>
        ) : null}
      </span>
    )}
    {invoice.customer_id && <code>{invoice.customer_id}</code>}
  </Definition>
);

const getPaymentDetail = invoice => (
  <Definition placeholder="--">
    <Amount value={invoice.amount_paid} currency={invoice.currency} />
    {invoice.partial_payment &&
    invoice.payments &&
    invoice.payments.items.length ? (
      <ContentToggler>
        <span>View Payment Details</span>
        <div
          className="full-width-item sub-entity-list"
          style={{ fontSize: 14 }}
        >
          <DataTable
            title="Payments"
            progressLoader={true}
            columns={[paymentId, paidOn, amount]}
            items={invoice.payments.items}
            noStripe={true}
          />
        </div>
      </ContentToggler>
    ) : (
      <React.Fragment>
        {invoice.payment_id && (
          <Link to={`/payments/${invoice.payment_id}`}>
            <code>{invoice.payment_id}</code>
          </Link>
        )}
        {invoice.paid_at && (
          <div>
            Paid on{' '}
            <Time value={invoice.paid_at} format="DD MMM YYYY, hh:mm a" />
          </div>
        )}
      </React.Fragment>
    )}
  </Definition>
);

export default props => {
  let { invoice, isLoading, statusMsg, editPaymentLink } = props;

  let status = invoice.status;
  const isDraft = status === 'draft';
  const isIssued = status === 'issued';
  const isPaid = status === 'paid';
  const isPartiallyPaid = status === 'partially_paid';
  const isCancelled = status === 'cancelled';
  const isExpired = status === 'expired';

  const isRazorXExperiment = true; // To be linked with RazorX as per experiment

  let isSmsOrEmailSent =
    invoice.sms_status === 'sent' || invoice.email_status === 'sent';

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-link text-primary icon--formal" />{' '}
            <strong>{invoice.id}</strong>
            <ShowWhen notMyRole="support finance">
              <div class="btn-toolbar pull-right">
                {(isDraft || isIssued) && (
                  <button
                    class="btn btn-primary btn-sm"
                    onClick={props.onIssue}
                  >
                    {isSmsOrEmailSent ? 'Send Again' : 'Send Link'}
                  </button>
                )}

                {isIssued && (
                  <button
                    class="btn btn-default btn-sm"
                    onClick={props.onCancel}
                  >
                    Cancel Link
                  </button>
                )}
              </div>
            </ShowWhen>
          </div>

          <div class="SliderPanel__Body">
            {invoice.type === 'invoice' && (
              <Banner cta="View Invoice" ctaUrl={'/invoices/' + invoice.id}>
                <span>
                  Following is the summary of the invoice. See invoice to view
                  all details.
                </span>
              </Banner>
            )}
            <div class="panel-body">
              <div class="list-group details-row-container">
                <EntityDetailRow
                  label="Payment For"
                  pairClass="description"
                  value={invoice.description || '--'}
                />
                <EntityDetailRow
                  label="Status"
                  value={() => <InvoiceStatusLabel status={invoice.status} />}
                />

                <EntityDetailRow
                  label="Amount"
                  value={() => (
                    <Amount
                      value={invoice.amount}
                      currency={invoice.currency}
                    />
                  )}
                />
                <ShowWhen featureEnabled="Invoice_Partial_Payments">
                  <React.Fragment>
                    {do {
                      const isPartialPayment = invoice.partial_payment;

                      <EntityDetailRow
                        label="Partial Payment"
                        value={() => (
                          <div
                            class={
                              isPartialPayment ? 'text-success' : 'text-danger'
                            }
                          >
                            {isPartialPayment ? 'Enabled' : 'Disabled'}
                            {isRazorXExperiment &&
                              isIssued && (
                                <AsyncBtn.Transparent
                                  onClick={() =>
                                    editPaymentLink({
                                      partial_payment: +!isPartialPayment,
                                    }).catch(({ errors }) => {
                                      props.showNotification({
                                        type: 'error',
                                        message:
                                          errors ||
                                          'Some Network error occured',
                                      });
                                    })
                                  }
                                  class="Button--Link"
                                  style={{ marginLeft: 12 }}
                                  pendingState={
                                    isPartialPayment ? 'Disabling' : 'Enabling'
                                  }
                                >
                                  {isPartialPayment ? 'Disable' : 'Enable'}
                                </AsyncBtn.Transparent>
                              )}
                          </div>
                        )}
                      />;
                    }}
                  </React.Fragment>
                </ShowWhen>
                <EntityDetailRow
                  label="Payment Link"
                  value={() => (
                    <CopyLink
                      url={invoice.short_url}
                      onCopy={() => {
                        if (invoice.type === 'link') {
                          window.rzpAnalytics({
                            eventCategory: 'Dashboard - Payment Links',
                            eventAction: 'Copy - Payment Link',
                            eventLabel: `payment_link_id=${invoice.id}`,
                          });
                        }
                      }}
                    />
                  )}
                />
                <EntityDetailRow label="Customer Details">
                  {getCustomerDetail(invoice)}
                </EntityDetailRow>

                <EntityDetailRow
                  label="Receipt"
                  value={
                    isRazorXExperiment && isIssued
                      ? () => (
                          <ReceiptField
                            value={invoice.receipt}
                            editPaymentLink={editPaymentLink}
                          />
                        )
                      : invoice.receipt || '--'
                  }
                />
                <ShowWhen featureEnabled="Invoice_Partial_Payments">
                  <EntityDetailRow label="Amount Paid">
                    {getPaymentDetail(invoice)}
                  </EntityDetailRow>
                </ShowWhen>

                <EntityDetailRow
                  label="Created At"
                  value={() => <Time value={invoice.date} />}
                />
                <EntityDetailRow
                  label={isExpired ? 'Expired On' : 'Expires On'}
                  value={
                    isRazorXExperiment && (isIssued || isPartiallyPaid)
                      ? () => (
                          <ExpiresOnField
                            value={invoice.expire_by}
                            editPaymentLink={editPaymentLink}
                          />
                        )
                      : () => (
                          <Time
                            value={invoice.expire_by}
                            format="DD MMM YYYY, hh:mm a"
                          />
                        )
                  }
                />

                <NestedEntityDetailRow label="Notes" value={invoice.notes} />

                <EntityDetailRow label="Created By">
                  {!!invoice.user ? (
                    <Definition>
                      {invoice.user.name}
                      {invoice.user.email}
                    </Definition>
                  ) : (
                    'API'
                  )}
                </EntityDetailRow>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

class ReceiptField extends React.Component {
  state = {
    isEditableMode: false,
    receipt: this.props.value || '',
  };

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });
    setTimeout(() => document.getElementsByName('receipt_no')[0].focus(), 10);
  };

  render() {
    let content = (
      <React.Fragment>
        {this.state.receipt || '--'}
        <Button.Transparent
          onClick={this.makeEditable}
          class="Button--Link"
          style={{ marginLeft: 12 }}
        >
          Change
        </Button.Transparent>
      </React.Fragment>
    );

    if (this.state.isEditableMode) {
      content = (
        <React.Fragment>
          <Input
            name="receipt_no"
            propagatedError={this.state.propagatedError}
            placeholder="Receipt No."
            class="Input--half_big Input--inline"
            required={true}
            value={this.state.receipt}
            onChange={e => {
              this.setState({
                receipt: e.target.value,
                propagatedError: '',
              });
            }}
          />
          <AsyncBtn.Transparent
            disabled={!this.state.receipt}
            onClick={() =>
              this.props
                .editPaymentLink({
                  receipt: this.state.receipt,
                })
                .then(resp => {
                  if (resp.data) {
                    this.setState({
                      isEditableMode: false,
                    });
                  }
                })
                .catch(({ errors }) => {
                  this.setState({
                    propagatedError: Array.isArray(errors)
                      ? errors[0]
                      : errors || 'Some Network error occured',
                  });
                })
            }
            class="Button--Link"
            style={{ position: 'absolute', top: 8, left: 208 }}
            pendingState="Saving"
          >
            Save
          </AsyncBtn.Transparent>
        </React.Fragment>
      );
    }

    return content;
  }
}

class ExpiresOnField extends React.Component {
  state = this.resetState();

  resetState() {
    return {
      isEditableMode: false,
      expire_by: this.props.value ? moment(this.props.value * 1000) : undefined,
      hasNoExpiry: this.props.value ? '0' : '1',
    };
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });
  };

  onDateChange = date => {
    const curExpiryByTime = this.state.expire_by;

    dateCalculator(date, curExpiryByTime, this.updateDate);
  };

  onTimeChange = date => {
    const curDate = this.state.expire_by;

    timeCalculator(date, curDate, this.updateDate);
  };

  updateDate = ts => {
    const newDate = moment(ts);

    this.setState({
      expire_by: newDate,
    });
  };

  render() {
    let content = (
      <React.Fragment>
        <Time value={this.props.value} format="DD MMM YYYY, hh:mm a" />
        <Button.Transparent
          onClick={this.makeEditable}
          class="Button--Link"
          style={{ marginLeft: 12 }}
        >
          Change
        </Button.Transparent>
      </React.Fragment>
    );

    if (this.state.isEditableMode) {
      content = (
        <React.Fragment>
          <Input.Check
            fieldLabel="No Expiry"
            defaultValue={this.props.value ? '0' : '1'}
            value={this.state.hasNoExpiry}
            onChange={e => {
              if (e.target.value == '0') {
                // 0 => unselected
                setTimeout(() => {
                  document
                    .querySelector('[data-name="expire_by_date"]')
                    .focus();
                  document
                    .querySelector('[data-name="expire_by_date"]')
                    .click();
                }, 10);
              }
              this.setState({
                hasNoExpiry: e.target.value,
              });
            }}
          />
          <Input.Group class="InputGroup--inline InputGroup--near">
            <div class="Input-content">
              <Input.ToCalendar
                data-name="expire_by_date"
                placeholder="15-04-2018"
                defaultValue={this.state.expire_by}
                disabled={this.state.hasNoExpiry === '1'}
                readOnly={true}
                onChange={this.onDateChange}
                size="half"
                addonAfter={<i class="i i-date-range" />}
                placement="topLeft"
                allowToday={true}
                disablePastDates={true}
              />
              {!!this.state.expire_by && (
                <Input.TimePicker
                  placeholder="11:59PM"
                  defaultValue={this.state.expire_by}
                  disabled={this.state.hasNoExpiry === '1'}
                  readOnly={true}
                  onChange={this.onTimeChange}
                  size="half"
                  addonAfter={<i class="i i-time" />}
                />
              )}
            </div>
          </Input.Group>
          <div>
            <Button
              onClick={() => {
                this.setState({ isEditableMode: false });
              }}
            >
              Discard
            </Button>
            <AsyncBtn.Primary
              onClick={() =>
                this.props
                  .editPaymentLink({
                    expire_by:
                      this.state.hasNoExpiry == '1'
                        ? null
                        : Math.floor(this.state.expire_by / 1000),
                  })
                  .then(resp => {
                    if (resp.data) {
                      this.setState(this.resetState());
                    }
                  })
                  .catch(({ errors }) => {
                    this.setState({
                      propagatedError: Array.isArray(errors)
                        ? errors[0]
                        : errors || 'Some Network error occured',
                    });
                  })
              }
              pendingState="Saving"
            >
              Save
            </AsyncBtn.Primary>
          </div>
        </React.Fragment>
      );
    }

    return content;
  }
}
