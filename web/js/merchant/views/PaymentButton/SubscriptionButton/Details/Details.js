import React from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { classList } from 'common/utils/rzp-utils';

import Time from 'common/ui/Time';
import Button from 'common/new-ui/Button';
import Definition from 'common/ui/Definition';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import PaymentReceipt from 'merchant/views/PaymentPages/PaymentPages/components/Modals/PaymentReceipt';
import Popover, { PopoverBody } from 'common/ui/Popover';
import TabsContainer from 'common/ui/Tabs';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { addPollInstance, saveReportConfigs } from 'merchant/reducers/reports';
import { updateHighlightButtonSettings } from 'merchant/reducers/subscriptionButtons/create';
import { setReceiptDetails, exportReportCSV } from 'merchant/views/PaymentPages/PaymentPages/model';
import { showNotification } from 'merchant_common/reducers/notifications';

import PaymentsList from './PaymentsList';
import SubscriptionsList from './SubscriptionsList';
import GetCodeModal from '../components/GetCodeModal';
import SettingsModal from '../components/SettingsModal';
import ItemDetails from './components/ItemDetails';

import track from './track';

/*
  Human readable reason to be displayed
  TODO: Only completed will be ever used for Subscription Button product, other statuses are for compatibility since payment pages entity is used internally
*/
const inActiveStatusReasonMap = {
  completed: 'One or more items are out of stock',
  expired: 'The link is expired',
  deactivated: 'You manually deactivated the link',
};

@connect(
  (state) => {
    return {
      user: state.session.user,
      isTestMode: state.session.mode === 'test',
      reportConfigs: state.reports.reportConfigs,
      currentHighlightedButtonSettings:
        state.subscription_button_create.current_highlighted_button_settings,
    };
  },
  {
    showNotification,
    openModal,
    closeModal,
    saveReportConfigs,
    addPollInstance,
    updateHighlightButtonSettings,
  },
)
@RTracking(() => window.rzpQ.component('SubscriptionButtonEntityComponent'))
export default class SubscriptionButtonEntityComponent extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      detailsCollapse: true,
      isExportInProgress: false,
      isPageReceiptModalOpened: false,
    };
  }

  componentDidMount() {
    if (!this.props.reportConfigs) {
      this.props.saveReportConfigs();
    }
  }

  saveLongPollInstances = (reportId, pollInstance) => {
    this.props.addPollInstance(reportId, pollInstance);
  };

  downloadReport = (extension) => {
    // eslint-disable-next-line no-shadow
    const { user, subscriptionButtonEntity, reportConfigs } = this.props;
    let configId;

    if (!reportConfigs.length) {
      return;
    }

    for (const idx in reportConfigs) {
      if (Object.prototype.hasOwnProperty.call(reportConfigs, idx)) {
        const config = reportConfigs[idx];
        if (
          config.type === 'payment_links' &&
          config.name.toLowerCase() === 'payment button report'
        ) {
          configId = config.id;
          break;
        }
      }
    }

    const promise = exportReportCSV(
      user,
      subscriptionButtonEntity,
      configId,
      this.saveLongPollInstances,
      extension,
    );

    if (promise && promise.then) {
      this.setState({
        isExportInProgress: true,
      });

      this.props.showNotification({
        type: 'success',
        message: 'Your report will download shortly',
      });

      promise.then((data) => {
        this.setState({
          isExportInProgress: false,
        });

        if (data.error || !data.url) {
          this.props.showNotification({
            type: 'error',
            message: data.error || 'Some error in downloading report',
          });
        }

        window.location = data.url;
      });
    } else {
      this.props.showNotification({
        type: 'error',
        message: 'Some error in downloading report',
      });
    }
  };

  openGetCodeModal = () => {
    track.lj.trackOpenGetCodeModal();

    this.props.openModal({
      size: 'medium',
      className: 'GetCodeModal',
      component: (
        <GetCodeModal
          title="Copy Button Code"
          paymentButton={this.props.subscriptionButtonEntity}
          closeModal={() => {
            this.props.closeModal();

            track.lj.trackCloseGetCodeModal();
          }}
          onCodeCopy={track.lj.trackCopyCode}
          onClickSeeDocumentation={track.lj.trackSeeDocumentation}
        />
      ),
    });
  };

  openSettingsModal = () => {
    track.lj.trackOptionsOpenSettings();

    this.props.openModal({
      size: 'medium',
      component: (
        <SettingsModal
          paymentSuccessMessage={
            this.props.subscriptionButtonEntity.settings.payment_success_message
          }
          editPaymentButton={this.props.editPaymentButton}
          track={{
            customMessage: track.lj.trackSettingsCustomMessage,
            closeModal: track.lj.trackSettingsCancel,
            save: track.lj.trackSettingsSave,
            saveFail: track.lj.trackSettingsSaveFail,
          }}
        />
      ),
    });
  };
  /*
  togglePageReceiptModal = () => {
    if (this.state.isPageReceiptModalOpened) {
      track.lj.trackSettingsReceiptConfigure();
    }
    this.setState({
      isPageReceiptModalOpened: !this.state.isPageReceiptModalOpened,
    });
  };
*/

  handleSavePaymentReceipt = (receipt) => {
    return setReceiptDetails(this.props.subscriptionButtonEntity.id, receipt)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Receipt details update successfully',
        });

        this.togglePageReceiptModal();

        this.props.updatesubscriptionButtonEntity({ receipt });
      })
      .catch((errors) => {
        this.props.showNotification({
          type: 'error',
          message: errors[1],
        });
      });
  };

  onClickOptions = () => {
    if (this.props.currentHighlightedButtonSettings) {
      this.props.updateHighlightButtonSettings(null);
    }

    track.lj.trackOptionsOpen();
  };

  render() {
    const {
      createdByUser,
      subscriptionButtonEntity,
      formItems,
      currentHighlightedButtonSettings,
    } = this.props;
    const { isPageReceiptModalOpened } = this.state;

    const highlightButtonSettings =
      currentHighlightedButtonSettings === subscriptionButtonEntity.id;

    const isActive = subscriptionButtonEntity.status === 'active';

    return (
      <React.Fragment>
        <div
          class={classList(
            'content-sm txn-details Entity--paymentpage Entity--paymentpage-v2 Entity--paymentpage-v3',
            'Entity--paymentbutton',
            this.state.detailsCollapse && 'Entity--paymentpage-collapse',
          )}
        >
          <div class="content-header">
            <Link to="/subscription_buttons">
              <i class="i i-arrow-back" /> Button List
            </Link>
            <i class="i i-chevron-right" /> {subscriptionButtonEntity.title}
          </div>

          <div class="panel panel-default">
            <div class="panel-heading">
              <div class="text">{subscriptionButtonEntity.title}</div>
              <div class="page-options">
                <Link
                  class="Button Button--primary--invert"
                  to={`/subscription_buttons/${subscriptionButtonEntity.id}/edit`}
                  onClick={track.lj.trackOptionsOpenEdit}
                >
                  <i class="i i-edit-outline" />
                </Link>

                <Link
                  class="Button Button--primary--invert"
                  to={`/subscription_buttons/new?duplicate_id=${subscriptionButtonEntity.id}`}
                  onClick={track.lj.trackOptionsOpenDuplicate}
                >
                  <i class="i i-copy" />
                </Link>

                <Dropdown>
                  <DropdownTrigger
                    class="dropdown-toggle Button Button--primary--invert"
                    onClick={this.onClickOptions}
                  >
                    <i class="i i-settings-outline" />
                    <i class="i i-chevron-down" />
                    {highlightButtonSettings && (
                      <Popover align="top" theme="dark" persistent={true}>
                        <PopoverBody>Configure post paymentmessage from options here.</PopoverBody>
                      </Popover>
                    )}
                  </DropdownTrigger>
                  <DropdownContent>
                    <ul class="dropdown-menu nav nav-stacked">
                      {/*
                      <li onClick={this.togglePageReceiptModal}>
                        <i class="i i-document" /> Payment Receipts
                      </li>
                    */}
                      <li onClick={this.openSettingsModal}>
                        {/* TODO:  Compress the i-checked-document svg icon */}
                        <i class="i i-checked-document" /> Post Payment Message
                      </li>
                    </ul>
                  </DropdownContent>
                </Dropdown>

                <Button.Primary onClick={this.openGetCodeModal}>Get Code</Button.Primary>
              </div>
            </div>

            <div class="panel-body">
              <div class="entity-details">
                <EntityDetailRow label="Button ID" value={subscriptionButtonEntity.id} />

                <EntityDetailRow
                  label="Button Status"
                  value={() => (
                    <div>
                      <PaymentPagesStatusLabel status={subscriptionButtonEntity.status} />

                      {!isActive && (
                        <Button.Transparent
                          class="Button--Link"
                          style={{ marginLeft: 12 }}
                          onClick={this.props.reActivateLink}
                        >
                          Activate
                        </Button.Transparent>
                      )}

                      <div style={{ marginTop: 4, color: '#8991ae' }}>
                        {inActiveStatusReasonMap[subscriptionButtonEntity.status_reason]}
                      </div>
                    </div>
                  )}
                />

                <EntityDetailRow
                  label="Created On"
                  value={() => <Time value={subscriptionButtonEntity.created_at} />}
                />

                <EntityDetailRow label="Created by">
                  {!!createdByUser ? (
                    <Definition>
                      {createdByUser.name}
                      {createdByUser.email}
                    </Definition>
                  ) : (
                    'API'
                  )}
                </EntityDetailRow>
              </div>

              <ItemDetails subscriptionButtonEntity={subscriptionButtonEntity} />
            </div>
          </div>
          <button
            type="button"
            class="btn-primary btn-sm panel-collapser collapsable-btn"
            onClick={(_) =>
              this.setState((prevState) => ({ detailsCollapse: !prevState.detailsCollapse }))
            }
          >
            {this.state.detailsCollapse ? (
              <span>
                Show More <i class="i i-chevron-down" />
              </span>
            ) : (
              <span>
                Show Less <i class="i i-chevron-up" />
              </span>
            )}
          </button>
        </div>
        <div class="content-sm txn-details Entity--paymentpage-v3 Entity--paymentbutton">
          {this.props.isTestMode && <TestModeBanner />}

          <TabsContainer
            class="item-details"
            tabNames={[
              <b key="subscription-payments">Subscription Payments</b>,
              <b key="one-time-Payments">One-Time Payments</b>,
            ]}
          >
            <SubscriptionsList entity={subscriptionButtonEntity} />

            <PaymentsList
              entity={subscriptionButtonEntity}
              downloadReport={this.downloadReport}
              isExportInProgress={this.state.isExportInProgress}
            />
          </TabsContainer>

          {isPageReceiptModalOpened && (
            <PaymentReceipt
              paymentPageEntity={subscriptionButtonEntity}
              formItems={formItems}
              handleClose={this.togglePageReceiptModal}
              handleSave={this.handleSavePaymentReceipt}
              trackingDetails={{
                isPaymentPage: false,
                via: 'details',
              }}
            />
          )}
        </div>
      </React.Fragment>
    );
  }
}
