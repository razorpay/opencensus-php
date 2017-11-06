import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { observer } from 'mobx-react';
import { toJS } from 'mobx';

import { adminFetch, adminPut, adminPost } from 'util/fetch';
import { closeModal, notifyError, notifySuccess, confirm } from 'common/modal';
import * as entityModals from './entityModals';
import { getDetailsViewMap } from './entity-resources';
import EntityRow from 'ui/EntityRow';
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

  downloadReports = () => {
    console.log('Downloading Reports....');
  };

  toggleFundsHoldOrRelease = () => {
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

    confirm(
      confirmMsg,
      () => {
        adminPut({
          route_name: 'merchant_action',
          url_params: {
            id: this.merchantId,
          },
          body: { action },
        })
          .then(response => {
            closeModal();
            notifySuccess(successMsg);
            this.model.updateDetails(response);
          })
          .catch(err => {
            notifyError(JSON.stringify(err.response));
          });
      },
      'Ok',
      'Cancel'
    );
  };

  captureScreenshot = () => {
    console.log('Capture Screenshot....');
  };

  // Lock / Unlock activation form
  toggleLockOnActivationForm = () => {
    const isCurrentlyLocked = this.model.merchant.details.merchant_details
      .locked;
    adminPut({
      route_name: 'merchant_activation_update',
      url_params: {
        id: this.merchantId,
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
        this.model.updateMerchantDetails(response);
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  // Enable / Disable live transactions
  toggleLiveTransactions = () => {
    const merchant = this.model.merchant;
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
        id: this.merchantId,
      },
    })
      .then(response => {
        if (response) {
          closeModal();
          notifySuccess(successMsg);
          this.model.updateDetails(response);
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  // Enable / Disable receipt emails
  toggleReceiptEmail = () => {
    const isReceiptEmailEnabled = this.model.merchant.details
      .receipt_email_enabled;
    let action, successMsg;

    if (isReceiptEmailEnabled) {
      action = 'disable_receipt_emails';
      successMsg = 'Receipt email disabled successfully';
    } else {
      action = 'enable_receipt_emails';
      successMsg = 'Receipt email enabled successfully';
    }

    adminPut({
      route_name: 'merchant_action',
      url_params: {
        id: this.merchantId,
      },
      body: { action },
    })
      .then(response => {
        closeModal();
        notifySuccess(successMsg);
        this.model.updateDetails(response);
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  toggleArchiveMerchant = () => {
    //TODO: Depending upon archive/unarchive, change this message;
    const message =
      'Are you sure you want to archive merchant?(Make sure you have attempted all ways of convincing him before doing this)';
    confirm(
      message,
      () => {
        console.log('Archive / Unarchive Merchant....');
      },
      'Ok',
      'Cancel'
    );
  };

  // Suspend / Unsuspend merchant
  toggleSuspension = () => {
    //TODO: Depending upon suspend/unsuspend, change this message;
    const message =
      'Are you sure you want to suspend merchant?(Make sure you have attempted all ways of convincing him before doing this)';
    confirm(
      message,
      () => {
        console.log('Suspend / Unsuspend Merchant....');
      },
      'Ok',
      'Cancel'
    );
  };

  getActionList() {
    const merchant = this.model.merchant;

    return (
      <aside class="">
        <div class="heading">Actions</div>
        <Link to={`/merchant/${this.merchantId}/login`} target="_blank">
          Login as Merchant
        </Link>
        <Link to={`/merchants/${this.merchantId}/activation`}>
          See Activation Form Details
        </Link>
        <Link to={`/merchants/${this.merchantId}/team`}>See Team Details</Link>
        <Link to={`/merchants/${this.merchantId}/stats`}>
          See Merchant Analytics Stats
        </Link>

        {/* Lock or Unlock activation form */}
        {merchant.details.merchant_details && (
          <div onClick={this.toggleLockOnActivationForm}>
            {merchant.details.merchant_details.locked ? 'Unlock' : 'Lock'}{' '}
            Activation Form
          </div>
        )}

        {/* Hold or Release funds */}
        {
          do {
            if (
              merchant.details.activated == 1 &&
              !merchant.details.hold_funds
            ) {
              <div onClick={this.toggleFundsHoldOrRelease}>
                Hold Merchant Funds
              </div>;
            } else if (merchant.details.hold_funds == 1) {
              <div onClick={this.toggleFundsHoldOrRelease}>
                Release Merchant Funds
              </div>;
            }
          }
        }

        {/* Toggle enable or disabled live transactions */}
        {
          do {
            if (merchant.details.activated == 1 && merchant.details.live == 0) {
              <div onClick={this.toggleLiveTransactions}>
                Enable Live Transactions
              </div>;
            } else if (merchant.details.live == 1) {
              <div onClick={this.toggleLiveTransactions}>
                Disable Live Transactions
              </div>;
            }
          }
        }

        {/* Toggle disbale or enable receipt email */}
        <div onClick={this.toggleReceiptEmail}>
          {merchant.details.receipt_email_enabled ? 'Disable' : 'Enable'}{' '}
          Receipt Email
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

        <div onClick={this.toggleArchiveMerchant}>
          {merchant.details.archived_at === null ? 'Archive' : 'Unarchive'}{' '}
          Merchant
        </div>

        <div onClick={this.toggleSuspension}>
          {merchant.details.suspended_at === null
            ? 'Suspend'
            : 'Unsuspend'}{' '}
          Merchant
        </div>
        <div onClick={actions.MarkReferred}>Mark as Referred</div>
        <div onClick={actions.EditTags}>Tag Merchant</div>
        <div onClick={actions.EditFeatures}>Feature Merchant</div>
        <div onClick={actions.MerchantBatchUpload}>Merchant Batch Upload</div>
        <div onClick={actions.UploadScreenshots}>Upload screenshots</div>
        <div onClick={this.captureScreenshot}>Capture Screenshots</div>
        <div onClick={actions.AddCredits}>Add Credits</div>

        <div class="btn-primary" onClick={this.downloadReports}>
          Download Report
        </div>
      </aside>
    );
  }

  getMainContent() {
    const detailsMap = getDetailsViewMap(toJS(this.model.merchant));

    return (
      <main class="">
        <div class="heading">
          Merchant: <b>{this.merchantId}</b> (View as Entity)
        </div>

        {detailsMap.map(item => {
          if (typeof item.value === 'function') {
            //TODO: Display the value directly (That value is to be something like ListViewToggler)

            return (
              <EntityRow
                key={item.label}
                label={item.label}
                value={item.value}
                toggleChildren={
                  item.toggleChildren ? item.toggleChildren() : undefined
                }
              />
            );
          } else {
            return (
              <EntityRow
                key={item.label}
                label={item.label}
                value={item.value}
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
        {this.getActionList()}

        {/* Content */}
        {this.getMainContent()}
      </div>
    );
  }
}
