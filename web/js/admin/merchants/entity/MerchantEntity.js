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

    this.merchantAction(action, successMsg);
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
        this.merchantAction(action, successMsg);
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

  function toggleFundsHoldOrRelease() {
    const merchant = this.model.merchant;

    let action, confirmMsg;
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
          id: this.merchantId,
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
        </div>
      )}

      {/* Hold or Release funds */}
      {
        do {
          if (merchant.details.activated == 1 && !merchant.details.hold_funds) {
            <div onClick={toggleFundsHoldOrRelease}>Hold Merchant Funds</div>;
          } else if (merchant.details.hold_funds == 1) {
            <div onClick={toggleFundsHoldOrRelease}>
              Release Merchant Funds
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
            </div>;
          } else if (merchant.details.live == 1) {
            <div onClick={toggleLiveTransactions}>
              Disable Live Transactions
            </div>;
          }
        }
      }

      {/* Toggle disbale or enable receipt email */}
      <div onClick={toggleReceiptEmail}>
        {merchant.details.receipt_email_enabled ? 'Disable' : 'Enable'} Receipt
        Email
      </div>

      <div onClick={actions.EditMethods}>Edit Methods</div>
      <div onClick={actions.AssignPricingPlan}>Assign Pricing</div>
      <div onClick={actions.AssignSchedule}>Assign Schedule</div>
      <div onClick={actions.AssignTerminal}>Assign Terminal</div>
      <div onClick={actions.AssignBanks}>Assign Banks</div>
      <div onClick={actions.AssignMerchantHandle}>Assign Merchant Handle</div>
      <div onClick={actions.AddAdjustment}>Add Adjustment</div>
      <div onClick={actions.CreateOffer}>Create Offer</div>
      <div onClick={actions.EditMerchant}>Edit Merchant</div>
      <div onClick={actions.EditMerchantEmail}>Edit Merchant Email</div>
      <div onClick={actions.EditBankAccountDetails}>
        Edit Bank Account Details
      </div>
      <div onClick={actions.AutoFillActivationForm}>
        Autofill Activation Form
      </div>
      <div onClick={actions.EditComment}>Edit Comment</div>

      <div onClick={toggleArchiveMerchant}>
        {merchant.details.archived_at === null ? 'Archive' : 'Unarchive'}{' '}
        Merchant
      </div>

      {typeof merchant.details.suspended_at !== 'undefined' && (
        <div onClick={toggleSuspension}>
          {merchant.details.suspended_at === null ? 'Suspend' : 'Unsuspend'}{' '}
          Merchant
        </div>
      )}
      <div onClick={actions.MarkReferred}>Mark as Referred</div>
      <div onClick={actions.EditTags}>Tag Merchant</div>
      <div onClick={actions.EditFeatures}>Feature Merchant</div>
      {merchant.features['live'] &&
        merchant.features['live'].assigned_features.indexOf('irctc_report') >
          -1 && (
          <div onClick={actions.MerchantBatchUpload}>Merchant Batch Upload</div>
        )}
      <div onClick={actions.UploadScreenshots}>Upload screenshots</div>
      <div onClick={captureScreenshot}>Capture Screenshots</div>
      <div onClick={actions.AddCredits}>Add Credits</div>

      <div class="btn-primary" onClick={actions.GenerateReports}>
        Download Report
      </div>
    </aside>
  );
};
