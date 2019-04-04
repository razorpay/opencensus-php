import React, { Component, Fragment } from 'react';
import { Link } from 'react-router-dom';
import { toJS } from 'mobx';
import { observer } from 'mobx-react';
import ShowWhen from 'admin/components/ShowWhen';
import fetch, { adminFetch, adminPut, adminPost } from 'common/fetch';
import {
  openModal,
  closeModal,
  notifyError,
  notifySuccess,
  confirm,
} from 'common/modal';
import * as entityModals from './entityModals';
import { getDetailsViewMap } from './entity-resources';
import EntityRow from 'ui/EntityRow';
import ToggleEntityRow from 'ui/ToggleEntityRow';
import Model from './model';
import AsyncButton from 'ui/AsyncButton';
import { isWorkflow, intersect } from 'common/util';
import { isPresent } from 'rzp/utils/rzp-utils';

import { isOrgHDFC } from 'admin/user';

let parentProps, merchantId;
const actions = {};

// Access actions using actions.FileName (FileName is the export name of that modal content in entity/index.js)
Object.keys(entityModals).map(key => {
  actions[key] = () => {
    const ModalContent = entityModals[key];
    openModal(<ModalContent props={parentProps} merchantId={merchantId} />);
  };
});

@observer
export default class MerchantEntity extends Component {
  constructor(props) {
    super();

    this.merchantId = props.match.params.id;

    this.model = new Model({
      fetchFn: adminFetch,
      merchantId: this.merchantId,
    });

    parentProps = this.model;
    merchantId = this.merchantId;
  }

  toHideEntity(toHide, permission, tag) {
    if (toHide) {
      return true;
    }

    if (permission) {
      if (!user.permissions.find(perm => perm === permission)) {
        return true;
      }
    }

    return false;
  }

  getMainContent() {
    const detailsMap = getDetailsViewMap(this.model);

    return (
      <main class="">
        <div class="heading">
          Merchant: <b>{this.merchantId}</b> (View as Entity)
        </div>

        {detailsMap.map(item => {
          if (this.toHideEntity(item.toHide, item.permission, item.tag)) {
            return;
          }

          if (item.children) {
            return (
              <ToggleEntityRow
                key={item.label}
                label={item.label}
                value={item.value}
                className={`vertical-center ${item.class ? item.class : ''}`}
              >
                {item.children()}
              </ToggleEntityRow>
            );
          } else {
            return (
              <EntityRow
                key={item.label}
                label={item.label}
                value={item.value}
                className={`vertical-center ${item.class ? item.class : ''}`}
              />
            );
          }
        })}
      </main>
    );
  }

  render() {
    return (
      <div class="entity-container merchant box">
        {/* Sidebar Action List */}
        <ActionsList
          model={this.model}
          merchantId={this.merchantId}
          actions={actions}
        />

        {/* Content */}
        {this.getMainContent()}
      </div>
    );
  }
}

/* Actions list on side bar */
const ActionsList = ({ model, merchantId, actions }) => {
  const merchant = model.merchant;
  const isDetailsLoading = !Object.keys(toJS(merchant.details)).length;
  const isFeaturesLoading = !Object.keys(toJS(merchant.features)).length;
  let isAdminsLoading = !Object.keys(toJS(merchant.adminsMap)).length;
  const isPartnerRequestsLoading = !Object.keys(toJS(merchant.partnerRequests))
    .length;
  const isSubmerchantsLoading = !merchant.submerchants;

  // Get bussiness banking account number
  let account_number = '';
  if (!isDetailsLoading) {
    const bankingAccount = toJS(merchant.details).merchant_details
      .banking_account;
    account_number = bankingAccount && bankingAccount.account_number;
  }

  // If user has no permission, then don't wait for this
  if (!user.permissions.find(perm => perm === 'view_all_admin')) {
    isAdminsLoading = false;
  }

  /* Confirmation Messages */
  const toggleArchiveMerchantCM = function() {
    const isAlreadyArchived = merchant.details.archived_at !== null;

    let todo;
    if (isAlreadyArchived) {
      todo = 'unarchive';
    } else {
      todo = 'archive';
    }
    return `Are you sure you want to ${todo} merchant? (Make sure you have attempted all ways of convincing him before doing this)`;
  };

  const toggleSuspensionCM = function() {
    const isAlreadySuspended = merchant.details.suspended_at != null;

    if (isAlreadySuspended) {
      return 'Are you sure you want to remove suspension from this merchant?';
    } else {
      return 'Are you sure you want to suspend merchant?(Make sure you have attempted all ways of convincing him before doing this)';
    }
  };

  /* Api call functions */
  function captureScreenshot() {
    fetch({
      url: '/admin/merchant/' + merchantId + '/screenshot',
      method: 'put',
    })
      .then(response => {
        notifySuccess(
          'Website screenshots capture started. Wait for notification on Slack'
        );
      })
      .catch(err => {
        notifyError(err);
      });
  }

  // Lock / Unlock activation form
  function toggleLockOnActivationForm() {
    const isCurrentlyLocked = merchant.details.merchant_details.locked;
    return adminPut({
      url: `live/merchant/activation/${merchantId}/update`,
      data: {
        locked: isCurrentlyLocked ? 0 : 1, // If already locked then send opposite
      },
    })
      .then(response => {
        notifySuccess(
          `Activation Form is now ${
            isCurrentlyLocked ? 'Unlocked' : 'Locked'
          } successfully`
        );
        model.updateMerchantDetails(response);
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  // Enable / Disable live transactions
  function toggleLiveTransactions() {
    let url, successMsg;

    if (merchant.details.activated == 1 && merchant.details.live == 0) {
      successMsg = 'Live transactions enabeld successfully.';
      url = `live/merchants/${merchantId}/live/enable`;
    } else if (merchant.details.live == 1) {
      url = `live/merchants/${merchantId}/live/disable`;
      successMsg = 'Live transactions disabled successfully.';
    }

    return adminPost(url)
      .then(response => {
        if (response) {
          closeModal();

          if (isWorkflow(response)) {
            notifySuccess('Workflow is created successfully.');
            return;
          }
          notifySuccess(successMsg);

          model.updateDetails(response);
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  function merchantAction(action, successMsg) {
    const data = {
      url: `live/merchants/${merchantId}/action`,
      data: { action },
    };

    return adminPut(data)
      .then(data => {
        if (data && (typeof data.success === 'undefined' || data.success)) {
          // In some cases, data is not data.data, but {success, data, error}
          if (isWorkflow(data)) {
            notifySuccess('Workflow is created successfully.');
            return;
          }
          notifySuccess(successMsg);

          model.updateDetails(data);
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  // Enable / Disable receipt emails
  function toggleReceiptEmail() {
    const isReceiptEmailEnabled = merchant.details.receipt_email_enabled;
    let action, successMsg;

    if (isReceiptEmailEnabled) {
      action = 'disable_receipt_emails';
      successMsg = 'Receipt email disabled successfully';
    } else {
      action = 'enable_receipt_emails';
      successMsg = 'Receipt email enabled successfully';
    }

    return merchantAction(action, successMsg);
  }

  // Suspend / Unsuspend merchant
  function toggleSuspension() {
    const isAlreadySuspended = merchant.details.suspended_at != null;
    let action, successMsg;

    if (isAlreadySuspended) {
      successMsg = 'Merchant suspension removed successfully';
      action = 'unsuspend';
    } else {
      successMsg = 'Merchant suspended successfully';
      action = 'suspend';
    }

    return fetch({
      url: `/admin/merchant/${merchantId}/action`,
      method: 'put',
      data: {
        action,
      },
    })
      .then(response => {
        if (response) {
          notifySuccess(successMsg);
          model.updateDetails(response);
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  // Archive / Unarchive merchant
  function toggleArchiveMerchant() {
    const isAlreadyArchived = merchant.details.archived_at !== null;
    let action, successMsg;

    if (isAlreadyArchived) {
      action = 'unarchive';
      successMsg = 'Merchant unarchived successfully';
    } else {
      action = 'archive';
      successMsg = 'Merchant archived successfully';
    }

    return merchantAction(action, successMsg);
  }

  function activateMerchant() {
    return fetch({
      url: '/admin/merchant/' + merchantId + '/activate',
      params: {
        dashboard: true,
      },
    })
      .then(response => {
        if (response) {
          notifySuccess('Merchant is successfully updated');
          if (isWorkflow(response)) {
            notifySuccess('Workflow is created successfully.');
            return;
          }
          model.updateDetails(response);
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  function toggleInternational() {
    let action, successMsg;
    if (merchant.details.international) {
      action = 'disable_international';
      successMsg = 'Merchant International disabled successfully';
    } else {
      action = 'enable_international';
      successMsg = 'Merchant International enabled successfully';
    }

    return merchantAction(action, successMsg);
  }

  function toggleFundsHoldOrRelease() {
    let action, successMsg;
    if (merchant.details.activated == 1 && !merchant.details.hold_funds) {
      successMsg = 'Merchant funds put on hold successfully';
      action = 'hold_funds';
    } else if (merchant.details.hold_funds == 1) {
      successMsg = 'Merchant funds released successfully';
      action = 'release_funds';
    }

    return merchantAction(action, successMsg);
  }

  function loginAsMerchant() {
    window.open(`/admin/merchant/${merchantId}/login`, '_blank');
  }

  function grantKeyAccessToMerchant(keyAccessValue) {
    return adminPut({
      url: `live/merchants/${merchantId}/update_key_access`,
      data: {
        has_key_access: keyAccessValue ? '1' : '0',
      },
    }).then(response => {
      if (response) {
        if (isWorkflow(response)) {
          return;
        }
        notifySuccess("Merchant's key access has been successfully updated.");
        model.updateDetails(response);
      }
    });
  }

  return (
    <aside class="">
      <div class="heading">Actions</div>

      <div class="group">
        <div class="group-heading">Merchant Summary</div>
        <ShowWhen permission="view_merchant_login">
          <a onClick={loginAsMerchant}>Login as Merchant</a>
        </ShowWhen>
        <ShowWhen permission="view_activation_form">
          <div onClick={actions.ViewTeam}>See Team Details</div>
        </ShowWhen>
        <ShowWhen permission="view_merchant_analytics">
          {!isDetailsLoading &&
            merchant.details.activated && (
              <Link
                to={{
                  pathname: `/merchants/${merchantId}/stats`,
                  search: merchant.details.business_banking
                    ? `?account_number=${account_number}`
                    : '',
                }}
              >
                See Merchant Analytics Stats
              </Link>
            )}
        </ShowWhen>
        <ShowWhen permission="edit_merchant_comments">
          <div onClick={actions.EditComment}>
            Edit Comment
            <i class="pull-right i i-comment" />
          </div>
        </ShowWhen>
        <ShowWhen permission="create_self_serve_report">
          <Link to={`/merchants/${merchantId}/report_config`}>
            Self Serve Report configs
          </Link>
        </ShowWhen>
        <ShowWhen permission="view_merchant_report">
          <div onClick={isDetailsLoading ? null : actions.GenerateReports}>
            Download Reports
            <i class="pull-right i i-download" />
            {isDetailsLoading && <div class="dot-loader">.</div>}
          </div>
        </ShowWhen>
      </div>

      <div class="group">
        <div class="group-heading">Activation</div>

        {/* Lock or Unlock activation form */}
        {merchant.details.merchant_details && (
          <ShowWhen
            permission={
              merchant.details.merchant_details.locked
                ? 'edit_merchant_lock_activation'
                : 'edit_merchant_unlock_activation'
            }
          >
            <AsyncButton
              onClick={toggleLockOnActivationForm}
              pendingClass="btn-pending"
              confirm={
                !merchant.details.merchant_details.locked &&
                !merchant.details.merchant_details.submitted &&
                'Merchant has not submitted the form yet. Do you still want to lock the form?'
              }
            >
              {merchant.details.merchant_details.locked ? 'Unlock' : 'Lock'}{' '}
              Activation Form
              <span class="spin-btn" />
              <i
                class={`pull-right i i-${
                  merchant.details.merchant_details.locked ? 'unlock' : 'lock'
                }`}
              />
            </AsyncButton>
          </ShowWhen>
        )}

        <ShowWhen permission="view_activation_form">
          <Link to={`/merchants/${merchantId}/activation`}>
            See Activation Form Details
          </Link>
        </ShowWhen>
        <ShowWhen permission="edit_merchant_methods">
          <div onClick={isDetailsLoading ? null : actions.EditMethods}>
            Edit Methods
            <i class="pull-right i i-money" />
            {isDetailsLoading && <div class="dot-loader">.</div>}
          </div>
        </ShowWhen>
        <ShowWhen permission="edit_merchant">
          <div
            onClick={
              isAdminsLoading || isDetailsLoading ? null : actions.EditMerchant
            }
          >
            Edit Merchant
            <i class="pull-right i i-edit-form" />
            {(isAdminsLoading || isDetailsLoading) && (
              <div class="dot-loader">.</div>
            )}
          </div>
        </ShowWhen>
        <ShowWhen permission="edit_merchant">
          <div onClick={isDetailsLoading ? null : actions.EditMerchantDetails}>
            Edit Advanced Details
            <i class="pull-right i i-edit-form" />
            {isDetailsLoading && <div class="dot-loader">.</div>}
          </div>
        </ShowWhen>
        <ShowWhen permission="edit_merchant_risk_threshold">
          <div onClick={isDetailsLoading ? null : actions.EditFraudScore}>
            Edit Fraud Score
            <i class="pull-right i i-edit-form" />
            {isDetailsLoading && <div class="dot-loader">.</div>}
          </div>
        </ShowWhen>

        <ShowWhen permission="edit_merchant_pricing">
          <div onClick={isDetailsLoading ? null : actions.AssignPricingPlan}>
            Assign Pricing
            <i class="pull-right i">%</i>
            {isDetailsLoading && <div class="dot-loader">.</div>}
          </div>
        </ShowWhen>
        <ShowWhen permission="schedule_assign">
          <div onClick={isDetailsLoading ? null : actions.AssignSchedule}>
            Assign Schedule
            <i class="pull-right i i-schedule" />
            {isDetailsLoading && <div class="dot-loader">.</div>}
          </div>
        </ShowWhen>
        <ShowWhen permission="edit_merchant_tags">
          <div onClick={isDetailsLoading ? null : actions.EditTags}>
            Tag Merchant
            <i class="pull-right i i-tag" />
            {isDetailsLoading && <div class="dot-loader">.</div>}
          </div>
        </ShowWhen>
        <ShowWhen permission="edit_merchant_features">
          <div onClick={isFeaturesLoading ? null : actions.EditFeatures}>
            Feature Merchant
            <i class="pull-right i i-tag" />
            {isFeaturesLoading && <div class="dot-loader">.</div>}
          </div>
        </ShowWhen>
        <ShowWhen permission="add_merchant_credits">
          <div onClick={actions.AddCredits}>Add Credits</div>
        </ShowWhen>
        {//only for activated merchants
        merchant.details.activated == 1 && (
          <ShowWhen permission="edit_merchant_key_access">
            <AsyncButton
              onClick={() =>
                grantKeyAccessToMerchant(!merchant.details.has_key_access)
              }
              pendingClass="btn-pending"
              confirm={`Are you sure you want to ${
                merchant.details.has_key_access ? 'remove' : 'grant'
              } Key Access for this merchant?`}
            >
              {merchant.details.has_key_access ? 'Remove' : 'Grant'} Key Access
              <span class="spin-btn" />
              <i class="pull-right i i-hand-stop" />
            </AsyncButton>
          </ShowWhen>
        )}
      </div>

      <div class="group">
        <div class="group-heading">Business Ops</div>

        <ShowWhen permission="add_merchant_adjustment">
          <div onClick={actions.AddAdjustment}>
            Add Adjustment
            <i class="pull-right i i-edit" />
          </div>
        </ShowWhen>
        <ShowWhen permission="edit_merchant_bank_detail">
          <div onClick={actions.EditBankAccountDetails}>
            Edit Bank Account Details
            <i class="pull-right i i-bank" />
          </div>
        </ShowWhen>
        <div onClick={actions.EditMerchantEmail}>
          Edit Merchant Email
          <i class="pull-right i i-email" />
        </div>
        <ShowWhen permission="create_merchant_offer">
          <div onClick={actions.CreateOffer}>
            Create Offer
            <i class="pull-right i i-money" />
          </div>
        </ShowWhen>
        <ShowWhen permission="assign_merchant_handle">
          <div onClick={actions.AssignMerchantHandle}>
            Assign Merchant Handle
            <i class="pull-right i">@</i>
          </div>
        </ShowWhen>
        <ShowWhen permission="create_virtual_accounts">
          <div onClick={actions.CreateVA}>
            Create Virtual Account
            <i class="pull-right i i-money" />
          </div>
        </ShowWhen>
        {merchant.features['live'] &&
          merchant.features['live'].assigned_features.indexOf('irctc_report') >
            -1 && (
            <div onClick={actions.MerchantBatchUpload}>
              Merchant Batch Upload
              <i class="pull-right i i-upload" />
            </div>
          )}
      </div>

      <div class="group">
        <div class="group-heading">Risk Actions</div>

        {/* Toggle disbale or enable receipt email */}
        <ShowWhen
          permission={
            merchant.details.international
              ? 'edit_merchant_disable_international'
              : 'edit_merchant_enable_international'
          }
        >
          <AsyncButton onClick={toggleInternational} pendingClass="btn-pending">
            {merchant.details.international ? 'Disable ' : 'Enable '}
            International
            <span class="spin-btn" />
            <i class="pull-right i i-globe" />
          </AsyncButton>
        </ShowWhen>

        {/* Hold or Release funds */}
        {do {
          if (merchant.details.activated == 1 && !merchant.details.hold_funds) {
            <ShowWhen permission="edit_merchant_hold_funds">
              <AsyncButton
                onClick={toggleFundsHoldOrRelease}
                pendingClass="btn-pending"
                confirm="Are you sure you want to hold funds for this merchant?"
              >
                Hold Merchant Funds
                <span class="spin-btn" />
                <i class="pull-right i i-hand-stop" />
              </AsyncButton>
            </ShowWhen>;
          } else if (merchant.details.hold_funds == 1) {
            <ShowWhen permission="edit_merchant_release_funds">
              <AsyncButton
                onClick={toggleFundsHoldOrRelease}
                pendingClass="btn-pending"
                confirm="Are you sure you want to release funds for this merchant?"
              >
                Release Merchant Funds
                <span class="spin-btn" />
                <i class="pull-right i i-thumps-up" />
              </AsyncButton>
            </ShowWhen>;
          }
        }}

        {/* Toggle disbale or enable receipt email */}
        <ShowWhen permission="edit_merchant_enable_receipt">
          <AsyncButton onClick={toggleReceiptEmail} pendingClass="btn-pending">
            {merchant.details.receipt_email_enabled ? 'Disable ' : 'Enable '}
            Receipt Email
            <span class="spin-btn" />
            <i class="pull-right i i-email" />
          </AsyncButton>
        </ShowWhen>

        {/* Toggle enable or disabled live transactions */}
        {merchant.details.activated && (
          <ShowWhen
            permission={
              merchant.details.live
                ? 'edit_merchant_disable_live'
                : 'edit_merchant_enable_live'
            }
          >
            <AsyncButton
              onClick={toggleLiveTransactions}
              pendingClass="btn-pending"
            >
              {merchant.details.live ? 'Disable' : 'Enable'} Live Transactions
              <span class="spin-btn" />
              <i
                class={`pull-right i i-${merchant.details.live ? 'no' : 'yes'}`}
              />
            </AsyncButton>
          </ShowWhen>
        )}

        {typeof merchant.details.suspended_at !== 'undefined' && (
          <ShowWhen
            permission={
              merchant.details.suspended_at === null
                ? 'edit_merchant_suspend'
                : 'edit_merchant_unsuspend'
            }
          >
            <AsyncButton
              onClick={toggleSuspension}
              pendingClass="btn-pending"
              confirm={toggleSuspensionCM()}
            >
              {merchant.details.suspended_at === null
                ? 'Suspend '
                : 'Unsuspend '}
              <i class="pull-right i i-power" />
              Merchant
              <span class="spin-btn" />
            </AsyncButton>
          </ShowWhen>
        )}
      </div>

      <div class="group">
        <div class="group-heading">PG Onboarding</div>

        <div onClick={actions.AssignTerminal}>
          Assign Terminal
          <i class="pull-right i i-terminal" />
        </div>

        <div onClick={actions.CreateTerminal}>
          Create Terminal
          <i class="pull-right i i-terminal" />
        </div>

        <ShowWhen permission="assign_merchant_banks">
          <div onClick={actions.AssignBanks}>
            Assign Banks
            <i class="pull-right i i-bank" />
          </div>
        </ShowWhen>
        <ShowWhen permission="merchant_autofill_form">
          <div
            onClick={isDetailsLoading ? null : actions.AutoFillActivationForm}
          >
            Autofill Activation Form
            <i class="pull-right i i-auto-fill" />
            {isDetailsLoading && <div class="dot-loader">.</div>}
          </div>
        </ShowWhen>
      </div>

      <PartnerNavItems
        isDetailsLoading={isDetailsLoading}
        isPartnerRequestsLoading={isPartnerRequestsLoading}
        isSubmerchantsLoading={isSubmerchantsLoading}
        merchant={merchant}
        actions={actions}
        merchantId={merchantId}
      />

      <div class="group">
        <div class="group-heading" />

        <ShowWhen permission="view_merchant_banks">
          <div onClick={actions.ViewBanks}>
            View Banks
            <i class="pull-right i i-bank" />
          </div>
        </ShowWhen>

        <ShowWhen permission="edit_merchant_mark_referred">
          <div onClick={isDetailsLoading ? null : actions.MarkReferred}>
            Mark as Referred
            <i class="pull-right i i-gift" />
            {isDetailsLoading && <div class="dot-loader">.</div>}
          </div>
        </ShowWhen>

        <ShowWhen permission="edit_merchant_screenshot">
          <div onClick={actions.UploadScreenshots}>
            Upload screenshots
            <i class="pull-right i i-upload" />
          </div>
        </ShowWhen>
        <ShowWhen permission="edit_merchant_screenshot">
          <div onClick={captureScreenshot}>
            Capture Screenshots
            <i class="pull-right i i-camera" />
          </div>
        </ShowWhen>
        {!isOrgHDFC() && (
          <ShowWhen permission="edit_merchant">
            <div onClick={isDetailsLoading ? null : actions.EditWhiteListIps}>
              Edit Whitelist IPs
              {isDetailsLoading && <div class="dot-loader">.</div>}
            </div>
          </ShowWhen>
        )}
      </div>
    </aside>
  );
};

function PartnerNavItems({
  isDetailsLoading,
  isPartnerRequestsLoading,
  isSubmerchantsLoading,
  merchant,
  actions,
  merchantId,
}) {
  const partnerPermissions = [
    'edit_merchant_requests',
    'edit_partners',
    'view_partners',
  ];
  return (
    <ShowWhen
      additionalCondition={user =>
        isPresent(intersect(partnerPermissions, user.permissions))
      }
    >
      <div class="group">
        <div class="group-heading">Partners</div>

        {(function() {
          const isLoading = isDetailsLoading || isPartnerRequestsLoading;
          const action = merchant.details.partner_type ? 'Remove' : 'Mark';
          return (
            <ShowWhen permission="edit_merchant_requests">
              <div onClick={isLoading ? null : actions.TogglePartnerType}>
                {isLoading ? (
                  <>
                    Fetching Partner Status <div class="dot-loader">.</div>{' '}
                  </>
                ) : (
                  <>{action} as partner</>
                )}
                <i class="pull-right i-partner" />
              </div>
            </ShowWhen>
          );
        })()}

        {!isDetailsLoading &&
          !!merchant.details.partner_type && (
            <ShowWhen permission="edit_partners">
              <div
                onClick={isSubmerchantsLoading ? null : actions.LinkSubmerchant}
              >
                Link Submerchant
                {isSubmerchantsLoading && <div class="dot-loader" />}
                <i class="pull-right i-user-plus" />
              </div>
            </ShowWhen>
          )}

        {!!merchant.details.partner_type && (
          <ShowWhen permission="view_partners">
            <Link to={`/merchants/${merchantId}/partner_config`}>
              Partner Settings
              <i class="pull-right i-partner" />
            </Link>
          </ShowWhen>
        )}
      </div>
    </ShowWhen>
  );
}
