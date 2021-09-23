import React from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { classList } from 'common/utils/rzp-utils';

import Time from 'common/ui/Time';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import Definition from 'common/ui/Definition';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import TestModeBanner from 'merchant/components/TestModeBanner';
import EditStock from 'merchant/views/PaymentPages/PaymentPages/components/EditStock';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import PaymentReceipt from 'merchant/views/PaymentPages/PaymentPages/components/Modals/PaymentReceipt';
import Popover, { PopoverBody } from 'common/ui/Popover';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { addPollInstance, saveReportConfigs } from 'merchant/reducers/reports';
import { updateHighlightButtonSettings } from 'merchant/reducers/paymentbuttons/create';
import { setReceiptDetails, exportReportCSV } from 'merchant/views/PaymentPages/PaymentPages/model';
import { showNotification } from 'merchant_common/reducers/notifications';
import { reportFormatOptions } from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectFormat';

import PaymentsList from './PaymentsList';
import GetCodeModal from '../components/GetCodeModal';
import SettingsModal from '../components/SettingsModal';

import track from './track';

/*
  Human readable reason to be displayed
  TODO: Only completed will be ever used for Payment Button product, other statuses are for compatibility since payment pages entity is used internally
*/
const inActiveStatusReasonMap = {
  completed: 'One or more items are out of stock',
  expired: 'The link is expired',
  deactivated: 'You manually deactivated the payment button',
};

@connect(
  (state) => {
    return {
      user: state.session.user,
      isTestMode: state.session.mode === 'test',
      reportConfigs: state.reports.reportConfigs,
      currentHighlightedButtonSettings:
        state.payment_button_create.current_highlighted_button_settings,
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
@RTracking(() => window.rzpQ.component('PaymentButtonEntity'))
export default class PaymentButtonEntity extends React.Component {
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

  getStatsTable(paymentButtonEntity) {
    return [
      {
        title: 'Total Payments',
        value: paymentButtonEntity.captured_payments_count,
      },
      {
        title: 'Total revenue',
        value: (
          <Amount
            value={paymentButtonEntity.total_amount_paid}
            currency={paymentButtonEntity.currency}
          />
        ),
      },
    ];
  }

  saveLongPollInstances = (reportId, pollInstance) => {
    this.props.addPollInstance(reportId, pollInstance);
  };

  downloadReport = (extension) => {
    const { user, paymentButtonEntity, reportConfigs } = this.props;
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
      paymentButtonEntity,
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
    track.openGetCodeModal();

    this.props.openModal({
      size: 'medium',
      className: 'GetCodeModal',
      component: (
        <GetCodeModal
          title="Copy Button Code"
          paymentButton={this.props.paymentButtonEntity}
          closeModal={() => {
            this.props.closeModal();

            track.closeGetCodeModal();
          }}
          onCodeCopy={track.copyCode}
          onClickSeeDocumentation={track.seeDocumentation}
        />
      ),
    });
  };

  openSettingsModal = () => {
    track.optionsOpenSettings('details');

    this.props.openModal({
      size: 'medium',
      component: (
        <SettingsModal
          paymentSuccessMessage={this.props.paymentButtonEntity.settings.payment_success_message}
          paymentSuccessRedirectUrl={
            this.props.paymentButtonEntity.settings.payment_success_redirect_url
          }
          editPaymentButton={this.props.editPaymentButton}
          track={{
            customMessage: track.settingsCustomMessage.bind(null, 'details'),
            closeModal: track.settingsCancel,
            save: track.settingsSave,
            saveFail: track.settingsSaveFail,
            customMessageCheckbox: track.customMessageCheckbox.bind(null, 'details'),
            redirectURLCheckbox: track.redirectURLCheckbox.bind(null, 'details'),
          }}
        />
      ),
    });
  };

  togglePageReceiptModal = () => {
    if (this.state.isPageReceiptModalOpened) {
      track.settingsReceiptConfigure();
    }

    this.setState((prevState) => ({
      isPageReceiptModalOpened: !prevState.isPageReceiptModalOpened,
    }));
  };

  handleSavePaymentReceipt = (receipt) => {
    return setReceiptDetails(this.props.paymentButtonEntity.id, receipt)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Receipt details update successfully',
        });

        this.togglePageReceiptModal();

        this.props.updatePaymentButtonEntity({ receipt });
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

    track.optionsOpen();
  };

  render() {
    const {
      createdByUser,
      paymentButtonEntity,
      editPaymentButton,
      formItems,
      reActivateLink,
      currentHighlightedButtonSettings,
      toggleManualActivation,
    } = this.props;
    const { isPageReceiptModalOpened } = this.state;

    const highlightButtonSettings = currentHighlightedButtonSettings === paymentButtonEntity.id;

    const isActive = paymentButtonEntity.status === 'active';

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
            <Link to="/paymentbuttons">
              <i class="i i-arrow-back" /> Button List
            </Link>
            <i class="i i-chevron-right" /> {paymentButtonEntity.title}
          </div>

          <div class="panel panel-default">
            <div class="panel-heading">
              <div class="text">{paymentButtonEntity.title}</div>
              <div class="page-options pull-right">
                <Link
                  class="Button Button--primary--invert"
                  to={`/paymentbuttons/${paymentButtonEntity.id}/edit`}
                  onClick={track.optionsOpenEdit}
                >
                  <i class="i i-edit-outline" />
                </Link>

                <Link
                  class="Button Button--primary--invert"
                  to={`/paymentbuttons/new?duplicate_id=${paymentButtonEntity.id}`}
                  onClick={track.optionsOpenDuplicate}
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
                        <PopoverBody>
                          Configure payment receipts, post payment message from options here.
                        </PopoverBody>
                      </Popover>
                    )}
                  </DropdownTrigger>
                  <DropdownContent>
                    <ul class="dropdown-menu nav nav-stacked">
                      <li onClick={this.togglePageReceiptModal}>
                        <i class="i i-document" /> Payment Receipts
                      </li>
                      <li onClick={this.openSettingsModal}>
                        {/* TODO:  Compress the i-checked-document svg icon */}
                        <i class="i i-checked-document" /> Post Payment Actions
                      </li>
                    </ul>
                  </DropdownContent>
                </Dropdown>

                <Button.Primary onClick={this.openGetCodeModal}>Get Code</Button.Primary>
              </div>
            </div>

            <div class="panel-body">
              <div class="entity-details">
                <EntityDetailRow label="Button ID" value={paymentButtonEntity.id} />

                <EntityDetailRow
                  label="Button Status"
                  value={() => (
                    <div>
                      <PaymentPagesStatusLabel status={paymentButtonEntity.status} />

                      <Button.Transparent
                        class="Button--Link"
                        style={{ marginLeft: 12 }}
                        onClick={isActive ? toggleManualActivation : reActivateLink}
                      >
                        {isActive ? 'Deactivate' : 'Activate'}
                      </Button.Transparent>

                      <div style={{ marginTop: 4, color: '#8991ae' }}>
                        {inActiveStatusReasonMap[paymentButtonEntity.status_reason]}
                      </div>
                    </div>
                  )}
                />

                <EntityDetailRow
                  label="Created On"
                  value={() => <Time value={paymentButtonEntity.created_at} />}
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

              <div class="item-details">
                <table>
                  <tbody>
                    {paymentButtonEntity.payment_page_items.map((pi, ix) => (
                      <tr key={ix}>
                        <td>
                          <div>
                            <b>{pi.item.name}</b>
                          </div>
                        </td>
                        <td>
                          <div>
                            <div class="title">Revenue</div>
                            <Amount
                              value={pi.total_amount_paid}
                              currency={paymentButtonEntity.currency}
                            />
                          </div>
                        </td>
                        <td>
                          <div>
                            <div class="title">Price</div>
                            <Amount
                              value={pi.item.amount}
                              currency={paymentButtonEntity.currency}
                            />
                          </div>
                        </td>
                        <td class="item-details-units">
                          <div>
                            <div class="title">Units Sold</div>
                            <EditStock
                              isRoleAllowedEdit
                              totalStock={pi.stock}
                              quantitySold={pi.quantity_sold}
                              editFn={editPaymentButton}
                              paymentPageItemId={pi.id}
                              trackerFn={() => {}}
                            />
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <button
            type="button"
            class="btn-primary btn-sm panel-collapser collapsable-btn"
            onClick={() =>
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

          <div class="stats">
            <b class="bold">Transactions</b>
            {this.getStatsTable(paymentButtonEntity).map((st, ix) => (
              <div key={ix}>
                {st.title}
                <b class="bold">{st.value}</b>
              </div>
            ))}

            <div class="report-download btn-toolbar pull-right">
              <div
                class="btn btn-default Button--invert report-download-trigger"
                disabled={this.state.isExportInProgress}
              >
                <i class="i i-download m-r" />
                {this.state.isExportInProgress ? 'Downloading...' : 'Download Report'}
              </div>
              <Popover align="bottom">
                <PopoverBody>
                  {reportFormatOptions.map((o, index) => (
                    <li
                      key={index}
                      type="button"
                      class="btn"
                      onClick={() => this.downloadReport(o.name)}
                      disabled={this.state.isExportInProgress}
                    >
                      {o.label}
                    </li>
                  ))}
                </PopoverBody>
              </Popover>
            </div>
          </div>

          <PaymentsList paymentPageId={paymentButtonEntity.id} />

          {isPageReceiptModalOpened && (
            <PaymentReceipt
              paymentPageEntity={paymentButtonEntity}
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
