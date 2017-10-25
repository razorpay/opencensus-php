import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { observer } from 'mobx-react';
import { toJS } from 'mobx';

import { adminFetch } from 'util/fetch';
import { openModal, confirm } from 'common/modal';
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

    parentProps = this.model.merchant;
  }

  downloadReports = () => {
    console.log('Downloading Reports....');
  };

  holdFunds = () => {
    confirm(
      'Are you sure you want to hold funds for this merchant?',
      () => {
        console.log('Handle Hold Funds....');
      },
      'Ok',
      'Cancel'
    );
  };

  releaseFunds = () => {
    confirm(
      'Are you sure you want to hold funds for this merchant?',
      () => {
        console.log('Release Merchant Funds....');
      },
      'Ok',
      'Cancel'
    );
  };

  captureScreenshot = () => {
    console.log('Capture Screenshot....');
  };

  // Lock / Unlock activation form
  toggleActivationFormLock = () => {
    console.log('Toggle Actionvation Form Lock....');
  };

  // Enable / Disable live transactions
  toggleLiveTransactions = () => {
    console.log('Enable / Disable Live transactions....');
  };

  // Enable / Disable receipt emails
  toggleReceiptEmail = () => {
    console.log('Enable / Disable Receipt Email....');
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
    let { merchant } = this.props;
    merchant = { details: {} }; // Dummy

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

        <div onClick={this.toggleActivationFormLock}>
          {merchant.details.lock === 0 ? 'Lock' : 'Unlock'} Activation Form
        </div>

        {merchant.details.activated == 1 &&
          merchant.details.hold_funds == 0 && (
            <div onClick={this.holdFunds}>Hold Merchant Funds</div>
          )}

        {merchant.details.hold_funds == 1 && (
          <div onClick={this.releaseFunds}>Release Merchant Funds</div>
        )}

        {merchant.details.activated == 1 &&
          merchant.details.live == 0 && (
            <div onClick={this.toggleLiveTransactions}>
              Enable Live Transactions
            </div>
          )}

        {merchant.details.live == 1 && (
          <div onClick={this.toggleLiveTransactions}>
            Disable Live Transactions
          </div>
        )}

        {merchant.details.receipt_email_enabled == 0 && (
          <div onClick={this.toggleReceiptEmail}>Enable Receipt Email</div>
        )}

        {merchant.details.receipt_email_enabled == 1 && (
          <div onClick={this.toggleReceiptEmail}>Disable Receipt Email</div>
        )}

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
