import React from 'react';
import { NavLink, Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Definition from 'common/ui/Definition';
import Spinner from 'common/ui/Spinner';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';

import CopyLink from 'merchant/components/CopyLink';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import Tooltip from 'common/ui/Tooltip';
import rolesList from 'merchant/helpers/permissions/roles-list';

import CustomerDetails from 'merchant/components/CustomerDetails';
import ReminderStepsDetails from './ReminderStepsDetails';
import PaymentDetails from './PaymentDetails';

import {
  EditExpiry,
  EditMinimumAmount,
  EditNotes,
  EditReceipt,
  EditBusinessSegment,
} from 'merchant/views/PaymentLinks/PaymentLinks/components/Edit/index';

import {
  trackDetailViewEdits,
  trackTogglePartialPayment,
  trackClickDuplicatePaymentLink,
} from 'merchant/views/PaymentLinks/PaymentLinks/ga';
import track from './track';

export default (props) => {
  const {
    user,
    paymentlink,
    isLoading,
    nextReminders,
    editPaymentLink,
    isRoleAllowedEdit,
    isAutoRemindersUpdating,
    onChangeSendAutoReminder,
    isMinimumFirstPaymentEnabled,
    isPaymentLinksRemindersEnabled,
  } = props;

  const status = paymentlink.status ? paymentlink.status.toLowerCase() : null;
  const isDraft = status === 'draft';
  const isIssued = status && ['issued', 'created'].indexOf(status.toLowerCase()) > -1;
  const isPaid = status === 'paid';
  const isPartiallyPaid = status === 'partially_paid';
  const isCancelled = status === 'cancelled';
  const isExpired = status === 'expired';

  const isSmsOrEmailSent = paymentlink.sms_status === 'sent' || paymentlink.email_status === 'sent';

  const isRemindersEnabled =
    paymentlink.reminder_status &&
    !(paymentlink.reminder_status === 'disabled' || paymentlink.reminder_status === 'failed');

  const isPaymentLinkClosed = isPaid || isCancelled || isExpired;

  const isContactDetailsAvl =
    paymentlink.customer_details &&
    !!(
      paymentlink.customer_details.customer_email || paymentlink.customer_details.customer_contact
    );

  const isUPILink = paymentlink.upi_link;
  const isPartialPayment = paymentlink.partial_payment;
  const generateCreatedBy = () => {
    if (!!paymentlink.user_id) {
      if (!!paymentlink.user) {
        return (
          <Definition>
            {paymentlink.user.name}
            {paymentlink.user.email}
          </Definition>
        );
      } else {
        return (
          <Definition>
            <span>User Id</span>
            <span>{paymentlink.user_id}</span>
          </Definition>
        );
      }
    } else {
      return <span>API</span>;
    }
  };
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-link text-primary icon--formal" /> <strong>{paymentlink.id}</strong>
            <div class="btn-toolbar pull-right">
              <NavLink
                onClick={trackClickDuplicatePaymentLink}
                class="btn Button--primary--invert"
                to={`/paymentlinks/new?duplicate_id=${paymentlink.id}`}
              >
                <i class="i i-copy" />
                <Tooltip theme="dark">Duplicate Payment Link</Tooltip>
              </NavLink>
              {(isRoleAllowedEdit || user.role === rolesList.RBL_AGENT) &&
                isContactDetailsAvl &&
                (isDraft || isIssued || isPartiallyPaid) && (
                  <button class="btn Button--primary" onClick={props.notifyCustomer}>
                    <Tooltip theme="dark">{isSmsOrEmailSent ? 'Resend Link' : 'Send Link'}</Tooltip>

                    <i className="i i-send" />
                  </button>
                )}
            </div>
          </div>

          <div class="SliderPanel__Body">
            <div class="panel-body">
              <div class="list-group details-row-container">
                {user.isPaymentLinkCreationV2Enabled && (
                  <EntityDetailRow label="Link Type">
                    {isUPILink ? (
                      <>
                        <i class="i i-upi m-r" /> UPI Payment Link
                      </>
                    ) : (
                      <>
                        <i class="i i-bank m-r" /> Standard Payment Link
                      </>
                    )}
                  </EntityDetailRow>
                )}
                <EntityDetailRow
                  label="Payment For"
                  pairClass="description"
                  value={paymentlink.description || '--'}
                />
                <EntityDetailRow
                  label="Status"
                  value={() => (
                    <div>
                      <InvoiceStatusLabel
                        status={paymentlink.status ? paymentlink.status.toLowerCase() : null}
                      />
                      {isRoleAllowedEdit && isIssued && (
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
                {!isUPILink && (
                  <EntityDetailRow
                    label="Partial Payment"
                    value={() => (
                      <div>
                        {isPartialPayment ? 'Enabled' : 'Disabled'}
                        {isRoleAllowedEdit && isIssued && (
                          <AsyncBtn.Transparent
                            onClick={() => {
                              const toEnablePartialPayment = +!isPartialPayment;

                              editPaymentLink({
                                partial_payment: toEnablePartialPayment,
                              });

                              trackTogglePartialPayment(
                                paymentlink.id,
                                'Toggle Partial Payment',
                                toEnablePartialPayment,
                              );
                            }}
                            class="Button--Link"
                            style={{ marginLeft: 12 }}
                            pendingState={isPartialPayment ? 'Disabling' : 'Enabling'}
                          >
                            {isPartialPayment ? 'Disable' : 'Enable'}
                          </AsyncBtn.Transparent>
                        )}
                        {isMinimumFirstPaymentEnabled && isPartialPayment && (
                          <EditMinimumAmount
                            isIssued={isIssued}
                            value={paymentlink.first_payment_min_amount}
                            maximum={paymentlink.amount}
                            currency={paymentlink.currency}
                            entityId={paymentlink.id}
                            editFn={editPaymentLink}
                            trackerFn={() => {}}
                            isRoleAllowedEdit={isRoleAllowedEdit}
                          />
                        )}
                      </div>
                    )}
                  />
                )}
                <EntityDetailRow
                  label="Amount"
                  value={() => (
                    <Amount value={paymentlink.amount} currency={paymentlink.currency} />
                  )}
                />
                <EntityDetailRow label="Amount Paid">
                  <PaymentDetails
                    paymentlink={paymentlink}
                    isPaymentlinksV2Enabled={user.isPaymentlinksV2Enabled}
                  />
                </EntityDetailRow>
                <EntityDetailRow
                  label="Link Url"
                  value={() => (
                    <CopyLink
                      url={paymentlink.short_url}
                      onCopy={() => {
                        window.rzpAnalytics?.({
                          eventCategory: 'Dashboard - Payment Links',
                          eventAction: 'Copy - Payment Link',
                          eventLabel: `payment_link_id=${paymentlink.id}`,
                        });
                        track.onCopyClick();
                      }}
                    />
                  )}
                />
                <EntityDetailRow label="Customer Details">
                  <CustomerDetails
                    name={paymentlink.customer_details.customer_name}
                    email={paymentlink.customer_details.customer_email}
                    emailStatus={paymentlink.customer_details.email_status}
                    customerId={paymentlink.customer_id}
                    contact={paymentlink.customer_details.contact}
                    smsStatus={paymentlink.customer_details.sms_status}
                  />
                </EntityDetailRow>
                {isPaymentLinksRemindersEnabled && (
                  <EntityDetailRow label="Reminders">
                    <React.Fragment>
                      <span>
                        <Input.Check
                          name="auto_reminders"
                          fieldLabel="Send auto reminders"
                          checked={isRemindersEnabled}
                          disabled={
                            !isContactDetailsAvl || isPaymentLinkClosed || isAutoRemindersUpdating
                          }
                          onChange={onChangeSendAutoReminder}
                          autoRender
                        />
                        {!isContactDetailsAvl && (
                          <Popover theme="dark" align="bottom">
                            <PopoverBody>
                              No contact details present for reminders to be sent
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
                {!isPaymentLinksRemindersEnabled && (
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
                  label={user.isPaymentlinksV2Enabled ? 'Reference Id' : 'Receipt No.'}
                  value={
                    isIssued
                      ? () => (
                          <EditReceipt
                            value={paymentlink.receipt}
                            entityId={paymentlink.id}
                            editFn={editPaymentLink}
                            trackerFn={(...args) => {
                              props.trackEditReceipt(...args);

                              trackDetailViewEdits(...args);
                            }}
                            isRoleAllowedEdit={isRoleAllowedEdit}
                            required={user.isInvoiceReceiptMandatory}
                            isPaymentlinksV2Enabled={user.isPaymentlinksV2Enabled}
                          />
                        )
                      : paymentlink.receipt || '--'
                  }
                />
                <EntityDetailRow label="Created By">{generateCreatedBy()}</EntityDetailRow>
                <EntityDetailRow
                  label="Created At"
                  value={() => <Time value={paymentlink.date || paymentlink.created_at} />}
                />
                <EntityDetailRow
                  label={isExpired ? 'Expired On' : 'Expires On'}
                  value={
                    isIssued
                      ? () => (
                          <EditExpiry
                            value={paymentlink.expire_by}
                            editFn={editPaymentLink}
                            entityId={paymentlink.id}
                            trackerFn={(...args) => {
                              props.trackEditExpiry(...args);

                              trackDetailViewEdits(...args);
                            }}
                            isRoleAllowedEdit={isRoleAllowedEdit}
                            isExpireByRequired={
                              user.isExpireByRequired ||
                              (user.isPaymentlinksV2Enabled && paymentlink.expire_by)
                            }
                          />
                        )
                      : () =>
                          paymentlink.expire_by ? (
                            <Time
                              value={isExpired ? paymentlink.expired_at : paymentlink.expire_by}
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
                      value={paymentlink.notes}
                      editFn={editPaymentLink}
                      isRoleAllowedEdit={isRoleAllowedEdit}
                      entityId={paymentlink.id}
                      trackerFn={(...args) => {
                        props.trackEditNotes(...args);

                        trackDetailViewEdits(...args);
                      }}
                    />
                  </EntityDetailRow>
                ) : (
                  <EditBusinessSegment
                    isRoleAllowedEdit={isRoleAllowedEdit}
                    value={paymentlink.notes}
                    editFn={editPaymentLink}
                    entityId={paymentlink.id}
                    trackerFn={trackDetailViewEdits}
                  />
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
