import React from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { classList } from 'common/utils/rzp-utils';

import TestModeBanner from 'merchant/components/TestModeBanner';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import Definition from 'common/ui/Definition';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Time from 'common/ui/Time';
import Amount from 'common/ui/Amount';
import CopyLink from 'merchant/components/CopyLink';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { addPollInstance, saveReportConfigs } from 'merchant/reducers/reports';
import { showNotification } from 'merchant_common/reducers/notifications';
import { trackDetailViewEdits, trackShareActions } from '../ga';
import { sendLink, exportReportCSV } from '../model';
import { reportFormatOptions } from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectFormat';
import track from './track';

import EditStock from 'merchant/views/PaymentPages/PaymentPages/components/EditStock';

import {
  EditExpiry,
  EditNotes,
} from 'merchant/views/PaymentLinks/PaymentLinks/components/Edit/index';
import ShareView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Share';
import CreateEmbedButton from 'merchant/views/PaymentPages/PaymentPages/components/Modals/CreateEmbedButton';

import PaymentsList from './PaymentsList';

import Button from 'common/new-ui/Button';
import Tooltip from 'common/ui/Tooltip';
import DropdownSettings from './DropdownSettings';
import DonationGoalTrackerPreview from '../Wysiwyg/DetailsSection/DonationGoalTrackerPreview';
import moment from 'moment';
import { parseGoalTrackerAmountValues } from '../Wysiwyg/DetailsSection/helpers';

// import mockPaymentPage from '../../Wysiwyg/data-mock';

/* Human readable reason to be displayed */
const inActiveStatusReasonMap = {
  completed: 'One or more items are out of stock',
  expired: 'The link is expired',
  deactivated: 'You manually deactivated the link',
};

const trackStock = (action, eventLabel) => {
  trackDetailViewEdits(action, eventLabel);

  action === 'Edit Stock' && track.updateStock();
};

const trackShare = (eventName, data) => {
  trackShareActions(eventName, data);

  track.shareModalEvents(eventName, data);
};

@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    reportConfigs: state.reports.reportConfigs,
    isMobileResolution: state.app.isMobileResolution,
  }),
  {
    showNotification,
    openModal,
    closeModal,
    saveReportConfigs,
    addPollInstance,
  },
)
@RTracking(() => window.rzpQ.component('PaymentPagesContainer'))
export default class PaymentPagesV3Entity extends React.Component {
  state = { detailsCollapse: true, isExportInProgress: false };

  componentDidMount() {
    if (!this.props.reportConfigs) {
      this.props.saveReportConfigs();
    }

    track.pageOpen();
  }

  getStatsTable(paymentPageEntity) {
    return [
      {
        title: 'Total Payments',
        value: paymentPageEntity.captured_payments_count,
      },
      {
        title: 'Total revenue',
        value: (
          <Amount
            value={paymentPageEntity.total_amount_paid}
            currency={paymentPageEntity.currency}
          />
        ),
      },
    ];
  }

  saveLongPollInstances = (reportId, pollInstance) => {
    this.props.addPollInstance(reportId, pollInstance);
  };

  downloadReport = (extension) => {
    const { user, paymentPageEntity, reportConfigs } = this.props;
    let configId;

    if (!reportConfigs.length) {
      return;
    }

    for (let i = 0; i < reportConfigs.length; i++) {
      const config = reportConfigs[i];
      if (config.type === 'payment_links' && config.name.toLowerCase() === 'payment page') {
        configId = config.id;
        break;
      }
    }

    const promise = exportReportCSV(
      user,
      paymentPageEntity,
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

      // eslint-disable-next-line consistent-return
      promise.then((data) => {
        this.setState({
          isExportInProgress: false,
        });

        if (data.error || !data.url) {
          return this.props.showNotification({
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

    track.downloadReport(extension);
  };

  openEmbedButtonView = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <CreateEmbedButton
          id={this.props.paymentPageEntity.id}
          handleClose={this.props.closeModal}
          trackerFn={noop}
          url={this.props.paymentPageEntity.short_url}
          color={this.props.merchantColor}
        />
      ),
    });
  };

  openShareView = () => {
    const { paymentPageEntity } = this.props;

    this.props.openModal({
      size: 'small',
      component: (
        <ShareView
          handleClose={this.props.closeModal}
          openModal={this.props.openModal}
          handleAction={sendLink.bind(null, paymentPageEntity.id)}
          showNotification={this.props.showNotification}
          title={paymentPageEntity.title}
          description={paymentPageEntity.description}
          trackerFn={trackShare}
          openEmbedButton={this.openEmbedButtonView}
          url={paymentPageEntity.short_url}
        />
      ),
    });

    trackDetailViewEdits('Click Share');
    track.share();
  };

  trackDateUpdate = (date, type) => {
    if (type === 'Cancel Expiry') {
      track.cancelExpiry();

      return;
    }

    if (type === 'No Expiry') {
      track.noExpiry();

      return;
    }

    if (type === 'Update Date') {
      track.updateDate();

      return;
    }

    if (!date) {
      track.tickExpiry();
    }
  };

  onClickDuplicatePage = () => {
    track.duplicatePage();
  };

  trackEditNotes = (changeType, modified) => {
    if (changeType === 'Save Notes') {
      track.saveNotes(modified);
    }

    if (changeType === 'Delete Notes (Confirmed)') {
      track.confirmDeleteNotes();
    }

    if (changeType === 'Delete Notes (Cancelled)') {
      track.cancelDeleteNotes();
    }

    if (changeType === 'Add New Notes') {
      track.addNewNote();
    }
  };

  render() {
    const {
      createdByUser,
      paymentPageEntity,
      editPaymentPage,
      toggleManualActivation,
      reActivateLink,
    } = this.props;

    // paymentPageEntity = mockPaymentPage;

    const isRoleAllowedEdit = this.props.user.isAllowedEdit('payment_pages');

    const status = paymentPageEntity.status;
    const statusReason = paymentPageEntity.status_reason;

    const isActive = status === 'active';
    const isExpired = !isActive && statusReason.toLowerCase() === 'expired';

    return (
      <React.Fragment>
        <div
          class={classList(
            'content-sm txn-details Entity--paymentpage Entity--paymentpage-v2 Entity--paymentpage-v3',
            this.state.detailsCollapse && 'Entity--paymentpage-collapse',
          )}
        >
          <div class="content-header">
            <Link to="/paymentpages">
              <i class="i i-arrow-back" /> All Payment Pages
            </Link>
            <i class="i i-chevron-right" /> {paymentPageEntity.title}
          </div>

          <div class="panel panel-default">
            <div class="panel-heading">
              <div class="text">{paymentPageEntity.title}</div>
              <div class="btn-toolbar">
                {isRoleAllowedEdit && isActive && (
                  <Button class="Button--primary--invert" onClick={this.openShareView}>
                    <i class="i i-share-outline" />
                    <Tooltip theme="dark" align="top">
                      Share Page
                    </Tooltip>
                  </Button>
                )}

                {isRoleAllowedEdit && (
                  <Link
                    to={`/paymentpages/new?duplicate_id=${paymentPageEntity.id}`}
                    onClick={this.onClickDuplicatePage}
                  >
                    <Button class="Button--primary--invert">
                      <i className="i i-duplicate" />
                    </Button>
                    <Tooltip theme="dark" align="top" className="rzp-tooltip-duplicate">
                      Duplicate Page
                    </Tooltip>
                  </Link>
                )}

                {isRoleAllowedEdit && <DropdownSettings paymentPageEntity={paymentPageEntity} />}

                {isRoleAllowedEdit && (
                  <Link to={`/paymentpages/${paymentPageEntity.id}/edit`}>
                    <Button.Primary>
                      <i class="i i-edit icon-border-bottom" /> Edit Page
                    </Button.Primary>
                  </Link>
                )}
              </div>
            </div>

            <div class="panel-body">
              <div class="entity-details">
                <EntityDetailRow
                  label="Page URL"
                  value={() => (
                    <CopyLink
                      url={paymentPageEntity.short_url}
                      onCopy={() => {
                        trackDetailViewEdits('Click Copy');
                        track.copyUrl();
                      }}
                    />
                  )}
                />
                <EntityDetailRow
                  label="Page Status"
                  value={() => (
                    <div>
                      <PaymentPagesStatusLabel status={status} />
                      {isRoleAllowedEdit && (
                        <Button.Transparent
                          class="Button--Link"
                          style={{ marginLeft: 12 }}
                          onClick={isActive ? toggleManualActivation : reActivateLink}
                        >
                          {isActive ? 'Deactivate' : 'Activate'}
                        </Button.Transparent>
                      )}
                      <div style={{ marginTop: 4, color: '#8991ae' }}>
                        {inActiveStatusReasonMap[statusReason]}
                      </div>
                    </div>
                  )}
                />

                <EntityDetailRow label="Payment Page ID" value={paymentPageEntity.id} />

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

                <EntityDetailRow
                  label="Created On"
                  value={() => <Time value={paymentPageEntity.created_at} />}
                />

                <EntityDetailRow
                  label={isExpired ? 'Expired On' : 'Expires On'}
                  value={() => (
                    <EditExpiry
                      value={paymentPageEntity.expire_by}
                      editFn={editPaymentPage}
                      entityId={paymentPageEntity.id}
                      isRoleAllowedEdit={isRoleAllowedEdit}
                      trackerFn={this.trackDateUpdate}
                    />
                  )}
                />

                <EntityDetailRow
                  label="Notes"
                  value={() => (
                    <EditNotes
                      value={paymentPageEntity.notes}
                      editFn={editPaymentPage}
                      entityId={paymentPageEntity.id}
                      trackerFn={(...args) => {
                        this.trackEditNotes(...args);

                        trackDetailViewEdits(...args);
                      }}
                      isRoleAllowedEdit={isRoleAllowedEdit}
                    />
                  )}
                />
                {paymentPageEntity.settings?.partner_webhook_settings?.partner_shiprocket ===
                  '1' && (
                  <EntityDetailRow
                    label="Shiprocket order creation"
                    value={() => (
                      <div>
                        <div class="status-label label label-success">Enabled</div>
                        <Link
                          style={{ marginLeft: 12 }}
                          to={`/paymentpages/${paymentPageEntity.id}/edit?modal=disableShiprocket`}
                        >
                          Disable
                        </Link>
                      </div>
                    )}
                  />
                )}
              </div>

              <div class="item-details">
                {this.props.user.isPPDonationGoalTracker &&
                  paymentPageEntity.settings &&
                  paymentPageEntity.settings.goal_tracker &&
                  paymentPageEntity.settings.goal_tracker.is_active === '1' && (
                    <DonationGoalTrackerPreview
                      {...paymentPageEntity.settings.goal_tracker}
                      meta_data={parseGoalTrackerAmountValues(
                        paymentPageEntity.settings.goal_tracker.meta_data,
                      )}
                      endDate={moment.unix(
                        paymentPageEntity.settings.goal_tracker.meta_data.goal_end_timestamp,
                      )}
                    />
                  )}
                <div class="table-container">
                  {paymentPageEntity.payment_page_items.map((pi, ix) => (
                    <div class="table" key={ix}>
                      <div>
                        <b>{pi.item.name}</b>
                      </div>
                      <div>
                        <div class="title">Revenue</div>
                        <Amount
                          value={pi.total_amount_paid}
                          currency={paymentPageEntity.currency}
                        />
                      </div>
                      <div>
                        <div class="title">Price</div>
                        <Amount value={pi.item.amount} currency={paymentPageEntity.currency} />
                      </div>
                      <div class="item-details-units">
                        <div class="title">Units Sold</div>
                        <EditStock
                          totalStock={pi.stock}
                          quantitySold={pi.quantity_sold}
                          editFn={editPaymentPage}
                          paymentPageItemId={pi.id}
                          trackerFn={trackStock}
                          isRoleAllowedEdit={isRoleAllowedEdit}
                        />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
          <button
            type="button"
            class="btn-primary btn-sm panel-collapser"
            onClick={() => {
              this.state.detailsCollapse && track.showMore();

              this.setState((prevState) => ({ detailsCollapse: !prevState.detailsCollapse }));
            }}
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

        <div class="content-sm txn-details Entity--paymentpage-v3">
          {this.props.mode === 'test' && <TestModeBanner />}

          <div class="stats">
            <div class="info">
              <b class="bold">Transactions</b>
              {this.getStatsTable(paymentPageEntity).map((st, ix) => (
                <div key={ix}>
                  {st.title}
                  <b class="bold">{st.value}</b>
                </div>
              ))}
            </div>

            <div class="report-download btn-toolbar">
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

          <PaymentsList paymentPageId={paymentPageEntity.id} />
        </div>
      </React.Fragment>
    );
  }
}

function noop() {}
