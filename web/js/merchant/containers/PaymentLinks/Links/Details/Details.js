import { NavLink, Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Definition from 'common/ui/Definition';
import Spinner from 'common/ui/Spinner';
import Banner from 'common/ui/Banner';
import DataTable from 'common/ui/Table/DataTable';
import { paymentId, amount, paidOn } from 'common/ui/item/pair';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';

import CopyLink from 'merchant/components/CopyLink';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import Tooltip from 'common/ui/Tooltip';
import ScheduledBanner from 'merchant/views/Settlements/components/ScheduledBanner';
import rolesList from 'merchant/helpers/permissions/roles-list';
import ShowWhen from 'merchant/components/ShowWhen';

import CustomerDetails from './CustomerDetails';
import ReminderStepsDetails from './ReminderStepsDetails';
import PaymentDetails from './PaymentDetails';

import {
  EditExpiry,
  EditMinimumAmount,
  EditNotes,
  EditReceipt,
  EditBusinessSegment,
} from 'merchant/containers/PaymentLinks/Edit/index';

import {
  trackDetailViewEdits,
  trackTogglePartialPayment,
  trackClickDuplicatePaymentLink,
} from 'merchant/containers/PaymentLinks/Links/ga';

export default props => {
  let {
    user,
    invoice,
    isLoading,
    statusMsg,
    nextReminders,
    editPaymentLink,
    isRoleAllowedEdit,
    isAutoRemindersUpdating,
    onChangeSendAutoReminder,
    isMinimumFirstPaymentEnabled,
    isPaymentLinksRemindersEnabled,
  } = props;

  let status = invoice.status;
  const isDraft = status === 'draft';
  const isIssued = status === 'issued';
  const isPaid = status === 'paid';
  const isPartiallyPaid = status === 'partially_paid';
  const isCancelled = status === 'cancelled';
  const isExpired = status === 'expired';

  let isSmsOrEmailSent =
    invoice.sms_status === 'sent' || invoice.email_status === 'sent';

  const isRemindersEnabled =
    invoice.reminder_status &&
    !(
      invoice.reminder_status === 'disabled' ||
      invoice.reminder_status === 'failed'
    );

  const isPaymentLinkClosed = isPaid || isCancelled || isExpired;

  const isContactDetailsAvl =
    invoice.customer && (invoice.customer.email || invoice.customer.contact);

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
            <div class="btn-toolbar pull-right">
              <NavLink
                onClick={trackClickDuplicatePaymentLink}
                class="btn Button--primary--invert"
                to={`/paymentlinks/new?duplicate_id=${invoice.id}`}
              >
                <i class="i i-copy" />
                <Tooltip theme="dark">Duplicate Payment Link</Tooltip>
              </NavLink>
              {(isRoleAllowedEdit || user.role === rolesList.RBL_AGENT) &&
                invoice.customer_id &&
                (isDraft || isIssued || isPartiallyPaid) && (
                  <button class="btn Button--primary" onClick={props.onIssue}>
                    <Tooltip theme="dark">
                      {isSmsOrEmailSent ? 'Resend Link' : 'Send Link'}
                    </Tooltip>

                    <i className="i i-send" />
                  </button>
                )}
            </div>
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
                  value={() => (
                    <div>
                      <InvoiceStatusLabel status={invoice.status} />
                      {isRoleAllowedEdit &&
                        isIssued && (
                          <Button.Transparent
                            class="Button--Link"
                            style={{ marginLeft: 12 }}
                            onClick={props.onCancel}
                          >
                            Cancel Link
                          </Button.Transparent>
                        )}
                    </div>
                  )}
                />

                <React.Fragment>
                  {do {
                    const isPartialPayment = invoice.partial_payment;

                    <EntityDetailRow
                      label="Partial Payment"
                      value={() => (
                        <div>
                          {isPartialPayment ? 'Enabled' : 'Disabled'}
                          {isRoleAllowedEdit &&
                            isIssued && (
                              <AsyncBtn.Transparent
                                onClick={() => {
                                  const toEnablePartialPayment = +!isPartialPayment;
                                  editPaymentLink({
                                    partial_payment: toEnablePartialPayment,
                                  });

                                  trackTogglePartialPayment(
                                    invoice.id,
                                    'Toggle Partial Payment',
                                    toEnablePartialPayment
                                  );
                                }}
                                class="Button--Link"
                                style={{ marginLeft: 12 }}
                                pendingState={
                                  isPartialPayment ? 'Disabling' : 'Enabling'
                                }
                              >
                                {isPartialPayment ? 'Disable' : 'Enable'}
                              </AsyncBtn.Transparent>
                            )}
                          {isMinimumFirstPaymentEnabled &&
                            isPartialPayment && (
                              <EditMinimumAmount
                                value={invoice.first_payment_min_amount}
                                maximum={invoice.amount}
                                currency={invoice.currency}
                                entityId={invoice.id}
                                editFn={editPaymentLink}
                                trackerFn={() => {}}
                                isRoleAllowedEdit={isRoleAllowedEdit}
                              />
                            )}
                        </div>
                      )}
                    />;
                  }}
                </React.Fragment>

                <EntityDetailRow
                  label="Amount"
                  value={() => (
                    <Amount
                      value={invoice.amount}
                      currency={invoice.currency}
                    />
                  )}
                />
                <EntityDetailRow label="Amount Paid">
                  <PaymentDetails invoice={invoice} />
                </EntityDetailRow>

                <EntityDetailRow
                  label="Link Url"
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
                  <CustomerDetails invoice={invoice} />
                </EntityDetailRow>

                {user.isRemindersEnabled &&
                  isPaymentLinksRemindersEnabled && (
                    <EntityDetailRow label="Reminders">
                      <React.Fragment>
                        <span>
                          <Input.Check
                            name="auto_reminders"
                            fieldLabel="Send auto reminders"
                            checked={isRemindersEnabled}
                            disabled={
                              !isContactDetailsAvl ||
                              isPaymentLinkClosed ||
                              isAutoRemindersUpdating
                            }
                            onChange={onChangeSendAutoReminder}
                            autoRender
                          />
                          {!isContactDetailsAvl && (
                            <Popover theme="dark" align="bottom">
                              <PopoverBody>
                                No contact details present for reminders to be
                                sent
                              </PopoverBody>
                            </Popover>
                          )}
                        </span>

                        {isContactDetailsAvl && (
                          <ReminderStepsDetails
                            isRemindersEnabled={isRemindersEnabled}
                            nextReminders={nextReminders}
                            isAutoRemindersUpdating={isAutoRemindersUpdating}
                            isPaymentLinkClosed={isPaymentLinkClosed}
                          />
                        )}
                      </React.Fragment>
                    </EntityDetailRow>
                  )}

                {user.isRemindersEnabled &&
                  !isPaymentLinksRemindersEnabled && (
                    <EntityDetailRow label="Reminders">
                      <div class="Input-content">
                        Reminders are not set for payment links.
                        <br />
                        Set it up{' '}
                        <Link target="_blank" to="/reminders">
                          here
                        </Link>
                      </div>
                    </EntityDetailRow>
                  )}

                <EntityDetailRow
                  label="Receipt No."
                  value={
                    isIssued
                      ? () => (
                          <EditReceipt
                            value={invoice.receipt}
                            entityId={invoice.id}
                            editFn={editPaymentLink}
                            trackerFn={trackDetailViewEdits}
                            isRoleAllowedEdit={isRoleAllowedEdit}
                            required={user.isInvoiceReceiptMandatory}
                          />
                        )
                      : invoice.receipt || '--'
                  }
                />

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

                <EntityDetailRow
                  label="Created At"
                  value={() => <Time value={invoice.date} />}
                />
                <EntityDetailRow
                  label={isExpired ? 'Expired On' : 'Expires On'}
                  value={
                    isIssued
                      ? () => (
                          <EditExpiry
                            value={invoice.expire_by}
                            editFn={editPaymentLink}
                            entityId={invoice.id}
                            trackerFn={trackDetailViewEdits}
                            isRoleAllowedEdit={isRoleAllowedEdit}
                            isExpireByRequired={user.isExpireByRequired}
                          />
                        )
                      : () =>
                          invoice.expire_by ? (
                            <Time
                              value={invoice.expire_by}
                              format="DD MMM YYYY, hh:mm a"
                            />
                          ) : (
                            'No Expiry'
                          )
                  }
                />

                {!user.isCustomNotesDropdownEnabled ? (
                  <EntityDetailRow label="Notes">
                    <EditNotes
                      value={invoice.notes}
                      editFn={editPaymentLink}
                      isRoleAllowedEdit={isRoleAllowedEdit}
                      entityId={invoice.id}
                      trackerFn={trackDetailViewEdits}
                    />
                  </EntityDetailRow>
                ) : (
                  <EditBusinessSegment
                    isRoleAllowedEdit={isRoleAllowedEdit}
                    value={invoice.notes}
                    editFn={editPaymentLink}
                    entityId={invoice.id}
                    trackerFn={trackDetailViewEdits}
                  />
                )}
                {user.isOndemandSettlementEnabled && (
                  <ShowWhen myRole="owner admin finance">
                    <ScheduledBanner fromWhere="Payment Pages" />
                  </ShowWhen>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
