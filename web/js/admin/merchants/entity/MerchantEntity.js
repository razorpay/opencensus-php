import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { observer } from 'mobx-react';
import ShowWhen from 'admin/components/ShowWhen';
import { adminFetch, adminPut, adminPost } from 'util/fetch';
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

let parentProps;
const actions = {};

// Access actions using actions.FileName (FileName is the export name of that modal content in entity/index.js)
Object.keys(entityModals).map(key => {
  actions[key] = () => {
    const ModalContent = entityModals[key];
    openModal(<ModalContent props={parentProps} />);
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
  }

  getMainContent() {
    const detailsMap = getDetailsViewMap(this.model);

    return (
      <main class="">
        <div class="heading">
          Merchant: <b>{this.merchantId}</b> (View as Entity)
        </div>

        {detailsMap.map(item => {
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

/* Side bar component */
const ActionsList = ({ model, merchantId, actions }) => {
  const merchant = model.merchant;

  /* Confirmation Messages */
  const toggleArchiveMerchantCM = function() {
    const isAlreadyArchived = merchant.details.archived_at !== null;

    let todo;
    if (isAlreadyArchived) {
      todo = 'unarchive';
    } else {
      todo = 'archive';
    }
    return `Are you sure you want to ${
      todo
    } merchant? (Make sure you have attempted all ways of convincing him before doing this)`;
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
    adminPut({}, '/admin/merchant/' + merchantId + '/screenshot')
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
      route_name: 'merchant_activation_update',
      url_params: {
        id: merchantId,
      },
      body: {
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
    let routeName, successMsg;

    if (merchant.details.activated == 1 && merchant.details.live == 0) {
      successMsg = 'Live transactions enabeld successfully.';
      routeName = 'merchant_live_enable';
    } else if (merchant.details.live == 1) {
      routeName = 'merchant_live_disable';
      successMsg = 'Live transactions disabled successfully.';
    }

    return adminPost({
      route_name: routeName,
      url_params: {
        id: merchantId,
      },
    })
      .then(response => {
        if (response) {
          closeModal();
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
      route_name: 'merchant_action',
      url_params: {
        id: merchantId,
      },
      body: { action },
    };

    return adminPut(data)
      .then(data => {
        if (data) {
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

    return merchantAction(action, successMsg);
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
    return adminFetch(
      {
        params: { dashboard: true },
      },
      '/admin/merchant/' + merchantId + '/activate'
    )
      .then(response => {
        if (response) {
          notifySuccess('Merchant is successfully updated');
          model.updateDetails(response);
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  function toggleInternational() {
    let action, successMsg;
    if (!merchant.details.international) {
      successMsg = 'Merchant International enabled successfully';
      action = 'enable_international';
    } else if (merchant.details.hold_funds == 1) {
      successMsg = 'Merchant International disabled successfully';
      action = 'disable_international';
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
        <Link to={`/merchants/${merchantId}/stats`}>
          See Merchant Analytics Stats
        </Link>
        <ShowWhen permission="edit_merchant_comments">
          <div onClick={actions.EditComment}>
            Edit Comment
            <i class="pull-right i i-comment" />
          </div>
        </ShowWhen>
        <ShowWhen permission="view_merchant_report">
          <div onClick={actions.GenerateReports}>
            Download Report
            <i class="pull-right i i-download" />
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
          <div onClick={actions.EditMethods}>
            Edit Methods
            <i class="pull-right i i-money" />
          </div>
        </ShowWhen>
        <ShowWhen permission="edit_merchant">
          <div onClick={actions.EditMerchant}>
            Edit Merchant
            <i class="pull-right i i-edit-form" />
          </div>
        </ShowWhen>

        <div onClick={actions.AssignPricingPlan}>
          Assign Pricing
          <i class="pull-right i">%</i>
        </div>
        <div onClick={actions.AssignSchedule}>
          Assign Schedule
          <i class="pull-right i i-schedule" />
        </div>
        <div onClick={actions.EditTags}>
          Tag Merchant
          <i class="pull-right i i-tag" />
        </div>
        <div onClick={actions.EditFeatures}>
          Feature Merchant
          <i class="pull-right i i-tag" />
        </div>
        <ShowWhen permission="add_merchant_credits">
          <div onClick={actions.AddCredits}>Add Credits</div>
        </ShowWhen>

        {merchant.details.activated == 0 && (
          <ShowWhen permission="edit_activate_merchant">
            <AsyncButton
              onClick={activateMerchant}
              pendingClass="btn-pending"
              confirm="Are you sure you have validated all merchant details, assigned pricing plan and terminal to merchant before activating?"
            >
              Activate Merchant
              <span class="spin-btn" />
              <i class="pull-right i i-done-all" />
            </AsyncButton>
          </ShowWhen>
        )}
        <ShowWhen
          permission={
            merchant.details.archived_at === null
              ? 'edit_merchant_archive'
              : 'edit_merchant_unarchive'
          }
        >
          <AsyncButton
            onClick={toggleArchiveMerchant}
            pendingClass="btn-pending"
            confirm={toggleArchiveMerchantCM()}
          >
            {merchant.details.archived_at === null ? 'Archive' : 'Unarchive'}{' '}
            <i
              class={`pull-right i i-${
                merchant.details.archived_at === null ? 'archive' : 'unarchive'
              }`}
            />
            Merchant
            <span class="spin-btn" />
          </AsyncButton>
        </ShowWhen>
      </div>

      <div class="group">
        <div class="group-heading">Business Ops</div>

        <div onClick={actions.AddAdjustment}>
          Add Adjustment
          <i class="pull-right i i-edit" />
        </div>
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
        <div onClick={actions.AssignMerchantHandle}>
          Assign Merchant Handle
          <i class="pull-right i">@</i>
        </div>
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
              ? 'edit_merchant_enable_international'
              : 'edit_merchant_disable_international'
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
        {
          do {
            if (
              merchant.details.activated == 1 &&
              !merchant.details.hold_funds
            ) {
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
          }
        }

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
              merchant.details.suspended_at
                ? 'edit_merchant_suspend'
                : 'edit_merchant_unsuspend'
            }
          >
            <AsyncButton
              onClick={toggleSuspension}
              pendingClass="btn-pending"
              confirm={toggleSuspensionCM()}
            >
              {merchant.details.suspended_at === null ? 'Suspend' : 'Unsuspend'}{' '}
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

        <div onClick={actions.AssignBanks}>
          Assign Banks
          <i class="pull-right i i-bank" />
        </div>
        <ShowWhen permission="merchant_autofill_form">
          <div onClick={actions.AutoFillActivationForm}>
            Autofill Activation Form
            <i class="pull-right i i-auto-fill" />
          </div>
        </ShowWhen>
      </div>

      <div class="group">
        <div class="group-heading" />
        <ShowWhen permission="edit_merchant_mark_referred">
          <div onClick={actions.MarkReferred}>
            Mark as Referred
            <i class="pull-right i i-gift" />
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
      </div>
    </aside>
  );
};
