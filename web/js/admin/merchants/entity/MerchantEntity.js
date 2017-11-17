import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { observer } from 'mobx-react';

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

const ActionsList = ({ model, merchantId, actions }) => {
  const merchant = model.merchant;

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
    adminPut({
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
          `Activation Form is now ${isCurrentlyLocked
            ? 'Unlocked'
            : 'Locked'} successfully`
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

    adminPut(data)
      .then(response => {
        notifySuccess(successMsg);
        model.updateDetails(response);
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

    merchantAction(action, successMsg);
  }

  // Suspend / Unsuspend merchant
  function toggleSuspension() {
    const isAlreadySuspended = merchant.details.suspended_at != null;
    let action, successMsg;

    if (isAlreadySuspended) {
      successMsg = 'Merchant suspension removed successfully';
      action = 'unsuspend';
      request();
    } else {
      successMsg = 'Merchant suspended successfully';
      action = 'suspend';

      confirm(
        'Are you sure you want to suspend merchant?(Make sure you have attempted all ways of convincing him before doing this)'
      ).then(_ => {
        merchantAction(action, successMsg);
      });
    }
  }

  // Archive / Unarchive merchant
  function toggleArchiveMerchant() {
    const isAlreadyArchived = merchant.details.archived_at !== null;
    let action, confirmMsg, successMsg;

    if (isAlreadyArchived) {
      action = 'unarchive';
      confirmMsg =
        'Are you sure you want to unarchive merchant? (Make sure you have attempted all ways of convincing him before doing this)';
      successMsg = 'Merchant unarchived successfully';
    } else {
      action = 'archive';
      confirmMsg =
        'Are you sure you want to archive merchant? (Make sure you have attempted all ways of convincing him before doing this)';
      successMsg = 'Merchant archived successfully';
    }

    confirm(confirmMsg).then(_ => merchantAction(action, successMsg));
  }

  function activateMerchant() {
    confirm(
      'Are you sure you have validated all merchant details, assigned pricing plan and terminal to merchant before activating?'
    ).then(_ => {
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
    });
  }

  function toggleFundsHoldOrRelease() {
    const merchant = model.merchant;

    let action, confirmMsg, successMsg;
    if (merchant.details.activated == 1 && !merchant.details.hold_funds) {
      confirmMsg = 'Are you sure you want to hold funds for this merchant?';
      successMsg = 'Merchant funds put on hold successfully';
      action = 'hold_funds';
    } else if (merchant.details.hold_funds == 1) {
      confirmMsg = 'Are you sure you want to release funds for this merchant?';
      successMsg = 'Merchant funds released successfully';
      action = 'release_funds';
    }

    confirm(confirmMsg).then(_ => {
      adminPut({
        route_name: 'merchant_action',
        url_params: {
          id: merchantId,
        },
        body: { action },
      })
        .then(response => {
          notifySuccess(successMsg);
          model.updateDetails(response);
        })
        .catch(err => {
          notifyError(JSON.stringify(err.response));
        });
    });
  }

  return (
    <aside class="">
      <div class="heading">Actions</div>
      <Link to={`/merchant/${merchantId}/login`} target="_blank">
        Login as Merchant
      </Link>
      <Link to={`/merchants/${merchantId}/activation`}>
        See Activation Form Details
      </Link>
      <Link to={`/merchants/${merchantId}/team`}>See Team Details</Link>
      <Link to={`/merchants/${merchantId}/stats`}>
        See Merchant Analytics Stats
      </Link>

      {/* Lock or Unlock activation form */}
      {merchant.details.merchant_details && (
        <div onClick={toggleLockOnActivationForm}>
          {merchant.details.merchant_details.locked ? 'Unlock' : 'Lock'}{' '}
          Activation Form
          <i
            class={`pull-right i i-${merchant.details.merchant_details.locked
              ? 'unlock'
              : 'lock'}`}
          />
        </div>
      )}

      {(true || merchant.details.activated == 0) && (
        <div onClick={activateMerchant}>
          Activate Merchant
          <i class="pull-right i i-done-all" />
        </div>
      )}

      {/* Hold or Release funds */}
      {
        do {
          if (merchant.details.activated == 1 && !merchant.details.hold_funds) {
            <div onClick={toggleFundsHoldOrRelease}>
              Hold Merchant Funds
              <i class="pull-right i i-hand-stop" />
            </div>;
          } else if (merchant.details.hold_funds == 1) {
            <div onClick={toggleFundsHoldOrRelease}>
              Release Merchant Funds
              <i class="pull-right i i-thumps-up" />
            </div>;
          }
        }
      }

      {/* Toggle enable or disabled live transactions */}
      {
        do {
          if (merchant.details.activated == 1 && merchant.details.live == 0) {
            <div onClick={toggleLiveTransactions}>
              Enable Live Transactions
              <i class="pull-right i i-yes" />
            </div>;
          } else if (merchant.details.live == 1) {
            <div onClick={toggleLiveTransactions}>
              Disable Live Transactions
              <i class="pull-right i i-no" />
            </div>;
          }
        }
      }

      {/* Toggle disbale or enable receipt email */}
      <div onClick={toggleReceiptEmail}>
        {merchant.details.receipt_email_enabled ? 'Disable' : 'Enable'} Receipt
        Email
        <i class="pull-right i i-email" />
      </div>

      <div onClick={actions.EditMethods}>
        Edit Methods
        <i class="pull-right i i-money" />
      </div>
      <div onClick={actions.AssignPricingPlan}>
        Assign Pricing
        <i class="pull-right i">%</i>
      </div>
      <div onClick={actions.AssignSchedule}>
        Assign Schedule
        <i class="pull-right i i-schedule" />
      </div>
      <div onClick={actions.AssignTerminal}>
        Assign Terminal
        <i class="pull-right i i-terminal" />
      </div>
      <div onClick={actions.AssignBanks}>
        Assign Banks
        <i class="pull-right i i-bank" />
      </div>
      <div onClick={actions.AssignMerchantHandle}>
        Assign Merchant Handle
        <i class="pull-right i">@</i>
      </div>
      <div onClick={actions.AddAdjustment}>
        Add Adjustment
        <i class="pull-right i i-edit" />
      </div>
      <div onClick={actions.CreateOffer}>
        Create Offer
        <i class="pull-right i i-money" />
      </div>
      <div onClick={actions.EditMerchant}>
        Edit Merchant
        <i class="pull-right i i-edit-form" />
      </div>
      <div onClick={actions.EditMerchantEmail}>
        Edit Merchant Email
        <i class="pull-right i i-email" />
      </div>
      <div onClick={actions.EditBankAccountDetails}>
        Edit Bank Account Details
        <i class="pull-right i i-bank" />
      </div>
      <div onClick={actions.AutoFillActivationForm}>
        Autofill Activation Form
        <i class="pull-right i i-auto-fill" />
      </div>
      <div onClick={actions.EditComment}>
        Edit Comment
        <i class="pull-right i i-comment" />
      </div>

      <div onClick={toggleArchiveMerchant}>
        {merchant.details.archived_at === null ? 'Archive' : 'Unarchive'}{' '}
        <i
          class={`pull-right i i-${merchant.details.archived_at === null
            ? 'archive'
            : 'unarchive'}`}
        />
        Merchant
      </div>

      {typeof merchant.details.suspended_at !== 'undefined' && (
        <div onClick={toggleSuspension}>
          {merchant.details.suspended_at === null
            ? 'Suspend'
            : 'Unsuspend'}{' '}
          <i class="pull-right i i-power" />
          Merchant
        </div>
      )}
      <div onClick={actions.MarkReferred}>
        Mark as Referred
        <i class="pull-right i i-gift" />
      </div>
      <div onClick={actions.EditTags}>
        Tag Merchant
        <i class="pull-right i i-tag" />
      </div>
      <div onClick={actions.EditFeatures}>
        Feature Merchant
        <i class="pull-right i i-tag" />
      </div>
      {merchant.features['live'] &&
        merchant.features['live'].assigned_features.indexOf('irctc_report') >
          -1 && (
          <div onClick={actions.MerchantBatchUpload}>
            Merchant Batch Upload
            <i class="pull-right i i-upload" />
          </div>
        )}
      <div onClick={actions.UploadScreenshots}>
        Upload screenshots
        <i class="pull-right i i-upload" />
      </div>
      <div onClick={captureScreenshot}>
        Capture Screenshots
        <i class="pull-right i i-camera" />
      </div>
      <div onClick={actions.AddCredits}>Add Credits</div>

      <div class="btn-primary" onClick={actions.GenerateReports}>
        Download Report
      </div>
    </aside>
  );
};
