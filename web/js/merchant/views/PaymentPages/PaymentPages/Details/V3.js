import React from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import rTracking from 'react-tracking';

import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import Definition from 'common/ui/Definition';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import Tooltip from 'common/ui/Tooltip';
import { dispatchWebViewEvent } from 'common/utils/reactNativeWebView';
import { classList, decodeHTMLEntities } from 'common/utils/rzp-utils';
import CopyLink from 'merchant/components/CopyLink';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import MagicCheckoutLabel from 'merchant/components/MagicCheckout/MagicCheckoutLabel';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { addPollInstance, saveReportConfigs } from 'merchant/reducers/reports';
import {
  EditExpiry,
  EditNotes,
} from 'merchant/views/PaymentLinks/PaymentLinks/components/Edit/index';
import { PAYMENT_PAGES_TYPES } from 'merchant/views/PaymentPages/PaymentPages/CreateEdit';
import DropdownSettings from 'merchant/views/PaymentPages/PaymentPages/Details/DropdownSettings';
import PaymentsList from 'merchant/views/PaymentPages/PaymentPages/Details/PaymentsList';
import track from 'merchant/views/PaymentPages/PaymentPages/Details/track';
import DonationGoalTrackerPreview from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/DetailsSection/DonationGoalTrackerPreview';
import { parseGoalTrackerAmountValues } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/DetailsSection/helpers';
import EditStock from 'merchant/views/PaymentPages/PaymentPages/components/EditStock';
import CreateEmbedButton from 'merchant/views/PaymentPages/PaymentPages/components/Modals/CreateEmbedButton';
import ShareView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Share';
import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';
import {
  trackDetailViewEdits,
  trackShareActions,
} from 'merchant/views/PaymentPages/PaymentPages/ga';
import { sendLink, exportReportCSV } from 'merchant/views/PaymentPages/PaymentPages/model';
import { getProductBaseLink } from 'merchant/views/PaymentPages/PaymentPages/utils';
import { reportFormatOptions } from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectFormat';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getBatchStatsTable } from 'merchant/views/PaymentPages/PaymentPages/helpers';
import { compose } from 'redux';

// import mockPaymentPage from '../../Wysiwyg/data-mock';

/* Human readable reason to be displayed */
const inActiveStatusReasonMap = {
  completed: 'One or more items are out of stock',
  expired: 'The link is expired',
  deactivated: 'You manually deactivated the link',
};

const trackShare = (eventName, data) => {
  trackShareActions(eventName, data);

  track.shareModalEvents(eventName, data);
};

class PaymentPagesV3Entity extends React.Component {
  state = { detailsCollapse: true, isExportInProgress: false };
  isStorefrontPage = location.hash === '#storefront';
  componentDidMount() {
    if (!this.props.reportConfigs) {
      this.props.saveReportConfigs();
    }

    track.pageOpen();
  }

  getStatsTable(paymentPageEntity) {
    const { captured_payments_count, total_amount_paid, currency } = paymentPageEntity;
    return [
      {
        title: 'Total Payments',
        value: captured_payments_count,
      },
      {
        title: 'Total revenue',
        value: <Amount value={total_amount_paid} currency={currency} />,
      },
    ];
  }

  saveLongPollInstances = (reportId, pollInstance) => {
    this.props.addPollInstance(reportId, pollInstance);
  };

  downloadReport = (extension) => {
    const { user, paymentPageEntity, reportConfigs } = this.props;
    let configId;

    if (!reportConfigs?.length) {
      this.props.showNotification({
        type: 'error',
        message: 'Something went wrong. Try again later.',
      });
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

    // if opened in webview (mobile app), sending native event with required data
    if (this.props.isWebView) {
      dispatchWebViewEvent({
        eventType: 'SHARE',
        data: {
          url: paymentPageEntity.short_url,
          title: paymentPageEntity.title,
        },
      });
    } else {
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
    }

    trackDetailViewEdits('Click Share');
    track.share();
  };

  trackDateUpdate = (date, type) => {
    /* 
      common component sometimes returning type in 
      first param and sometimes in second. needs to be fixed.
    */

    if (type === 'Cancel Expiry') {
      track.cancelExpiry();
    } else if (type === 'No Expiry') {
      track.noExpiry(true);
    } else if (type === 'Update Date') {
      track.updateDate();
      track.noExpiry(false);
    }

    if (date === 'Edit Expiry') {
      track.changeExpiry();
    } else if (date === 'Edit Expiry (Saved)') {
      track.saveExpiry();
    }
  };

  trackStock = (action, eventLabel) => {
    trackDetailViewEdits(action, eventLabel);

    const { id, short_url } = this.props.paymentPageEntity;

    action === 'Edit Stock' &&
      track.updateStock({
        pageId: id,
        published_page_url: short_url,
        product_page: this.isStorefrontPage ? 'Storefront Page' : 'Payment Page',
      });
  };

  onClickDuplicatePage = () => {
    const { id, short_url } = this.props.paymentPageEntity;
    track.duplicatePage({
      pageId: id,
      published_page_url: short_url,
      product_page: this.isStorefrontPage ? 'Storefront Page' : 'Payment Page',
    });
  };

  onClickEditPage = () => {
    const { id, short_url } = this.props.paymentPageEntity;
    track.editPage({
      pageId: id,
      published_page_url: short_url,
      product_page: this.isStorefrontPage ? 'Storefront Page' : 'Payment Page',
    });
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
      reActivateLink,
      toggleManualActivation,
      isStorefrontPage,
      isNoExpiryMandatory,
      isBatchPaymentPages,
      hasPendingPayments,
      pendingPayments,
      user,
    } = this.props;
    const { isExportInProgress } = this.state;

    const { id, title, short_url } = paymentPageEntity;

    const isRoleAllowedEdit = this.props.user.isAllowedEdit('payment_pages');

    const status = paymentPageEntity.status;
    const statusReason = paymentPageEntity.status_reason;
    const countryCode = user.merchant.country_code;

    const isActive = status === 'active';
    const isExpired = !isActive && statusReason?.toLowerCase() === 'expired';
    const isMagicCheckoutOrder = paymentPageEntity?.settings?.one_click_checkout === '1';
    const productBaseUrl = getProductBaseLink(isStorefrontPage, id, isBatchPaymentPages);
    const isShareButtonShown = isRoleAllowedEdit && isActive && !isStorefrontPage;

    let type = PAYMENT_PAGES_TYPES.payment_page;

    if (isStorefrontPage) {
      type = PAYMENT_PAGES_TYPES.storefront;
    } else if (isBatchPaymentPages) {
      type = PAYMENT_PAGES_TYPES.batch_payment_page;
    }

    const isDownloadReport = !isStorefrontPage && !isBatchPaymentPages;

    const statsTable = isBatchPaymentPages
      ? getBatchStatsTable({ paymentPageEntity, pendingPayments })
      : this.getStatsTable(paymentPageEntity);
    const isDonationGoalTrackerPreview =
      paymentPageEntity?.settings?.goal_tracker?.is_active === '1' && !isBatchPaymentPages;

    return (
      <React.Fragment>
        <div
          className={classList(
            'content-sm txn-details Entity--paymentpage Entity--paymentpage-v2 Entity--paymentpage-v3',
            this.state.detailsCollapse && 'Entity--paymentpage-collapse',
          )}
        >
          <div className="content-header">
            <Link to={isBatchPaymentPages ? BATCH_PAYMENT_PAGES_BASE_URL : '/paymentpages'}>
              <i className="i i-arrow-back" />
              {isBatchPaymentPages ? 'All Batch Payment Pages' : 'All Payment Pages'}
            </Link>
            <i className="i i-chevron-right" /> {decodeHTMLEntities(title)}
          </div>

          <div className="panel panel-default">
            <div className="panel-heading">
              <div className="text">{decodeHTMLEntities(title)}</div>
              <div className="btn-toolbar">
                {isBatchPaymentPages && (
                  <Link to={`/paymentpages/batchuploads/${id}/${title}`}>
                    <Button.Primary>
                      <i className="i icon-border-bottom" /> View Batch Details
                    </Button.Primary>
                  </Link>
                )}
                {isShareButtonShown && (
                  <Button className="Button--primary--invert" onClick={this.openShareView}>
                    <i className="i i-share-outline" />
                    <Tooltip theme="dark" align="top">
                      Share Page
                    </Tooltip>
                  </Button>
                )}
                {isRoleAllowedEdit && (
                  <Link
                    to={`/paymentpages/new?duplicate_id=${paymentPageEntity.id}&type=${type}`}
                    onClick={this.onClickDuplicatePage}
                  >
                    <Button className="Button--primary--invert">
                      <i className="i i-duplicate" />
                    </Button>
                    <Tooltip theme="dark" align="top" className="rzp-tooltip-duplicate">
                      Duplicate Page
                    </Tooltip>
                  </Link>
                )}

                {isRoleAllowedEdit && (
                  <DropdownSettings
                    paymentPageEntity={paymentPageEntity}
                    isStorefrontPage={isStorefrontPage}
                    isBatchPaymentPages={isBatchPaymentPages}
                  />
                )}

                {isRoleAllowedEdit && (
                  <Link to={`${productBaseUrl}/edit`} onClick={this.onClickEditPage}>
                    <Button.Primary>
                      <i className="i i-edit icon-border-bottom" /> Edit Page
                    </Button.Primary>
                  </Link>
                )}
              </div>
            </div>

            <div className="panel-body">
              <div className="entity-details">
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
                          className="Button--Link"
                          style={{ marginLeft: 12 }}
                          onClick={
                            isActive
                              ? () => {
                                  track.deactivatePageClicked({
                                    pageId: id,
                                    published_page_url: short_url,
                                    product_page: this.isStorefrontPage
                                      ? 'Storefront Page'
                                      : 'Payment Page',
                                  });
                                  toggleManualActivation();
                                }
                              : reActivateLink
                          }
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

                {isMagicCheckoutOrder && (
                  <EntityDetailRow label="Checkout Type" value={MagicCheckoutLabel} />
                )}

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
                      isExpireByRequired={!isNoExpiryMandatory}
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
                        <div className="status-label label label-success">Enabled</div>
                        <Link
                          style={{ marginLeft: 12 }}
                          to={`${productBaseUrl}/edit?modal=shiprocket`}
                        >
                          Disable
                        </Link>
                      </div>
                    )}
                  />
                )}
              </div>

              <div className="item-details">
                {isDonationGoalTrackerPreview ? (
                  <DonationGoalTrackerPreview
                    {...paymentPageEntity.settings.goal_tracker}
                    meta_data={parseGoalTrackerAmountValues(
                      paymentPageEntity.settings.goal_tracker.meta_data,
                    )}
                    endDate={moment.unix(
                      paymentPageEntity.settings.goal_tracker.meta_data.goal_end_timestamp,
                    )}
                    currency={paymentPageEntity.currency}
                    countryCode={countryCode}
                  />
                ) : null}
                {!isBatchPaymentPages ? (
                  <div className="table-container">
                    {paymentPageEntity?.payment_page_items?.map((pi, ix) => {
                      const itemName = decodeHTMLEntities(pi?.item?.name);
                      return (
                        <div className="table" key={ix}>
                          <div>
                            <b>{itemName}</b>
                          </div>
                          <div>
                            <div className="title">Revenue</div>
                            <Amount
                              value={pi.total_amount_paid}
                              currency={paymentPageEntity.currency}
                            />
                          </div>
                          <div>
                            <div className="title">Price</div>
                            <Amount
                              value={pi.item?.amount ?? 0}
                              currency={paymentPageEntity.currency}
                            />
                          </div>
                          <div className="item-details-units">
                            <div className="title">Units Sold</div>
                            <EditStock
                              totalStock={pi.stock}
                              quantitySold={pi.quantity_sold}
                              editFn={editPaymentPage}
                              paymentPageItemId={!isStorefrontPage ? pi.id : pi.catalog_id}
                              trackerFn={this.trackStock}
                              isRoleAllowedEdit={isRoleAllowedEdit}
                              isStorefrontPage={isStorefrontPage}
                              storefrontCatalogStatus={pi.catalog_status}
                            />
                          </div>
                        </div>
                      );
                    })}
                  </div>
                ) : null}
              </div>
            </div>
          </div>
          <button
            type="button"
            className="btn-primary btn-sm panel-collapser"
            onClick={() => {
              this.state.detailsCollapse &&
                track.showMore({
                  pageId: id,
                  published_page_url: short_url,
                  product_page: this.isStorefrontPage ? 'Storefront Page' : 'Payment Page',
                });
              this.setState((prevState) => ({ detailsCollapse: !prevState.detailsCollapse }));
            }}
          >
            {this.state.detailsCollapse ? (
              <span>
                Show More <i className="i i-chevron-down" />
              </span>
            ) : (
              <span>
                Show Less <i className="i i-chevron-up" />
              </span>
            )}
          </button>
        </div>

        <div className="content-sm txn-details Entity--paymentpage-v3">
          {this.props.mode === 'test' && <TestModeBanner />}

          <div className="stats">
            <div className="info">
              <b className="bold">Transactions</b>
              {hasPendingPayments && <Spinner />}
              {!hasPendingPayments &&
                statsTable.map((st, ix) => (
                  <div key={ix}>
                    {st.title}
                    <b className="bold">{st.value}</b>
                  </div>
                ))}
            </div>

            {isDownloadReport && (
              <div className="report-download btn-toolbar">
                <div
                  className="btn btn-default Button--invert report-download-trigger"
                  disabled={isExportInProgress}
                >
                  <i className="i i-download m-r" />
                  {isExportInProgress ? 'Downloading...' : 'Download Report'}
                </div>
                <Popover align="bottom">
                  <PopoverBody>
                    {reportFormatOptions.map((o, index) => (
                      <li
                        key={index}
                        type="button"
                        className="btn"
                        onClick={this.downloadReport.bind(null, o.name)}
                        disabled={isExportInProgress}
                      >
                        {o.label}
                      </li>
                    ))}
                  </PopoverBody>
                </Popover>
              </div>
            )}
          </div>

          <PaymentsList paymentPageId={paymentPageEntity.id} isStorefrontPage={isStorefrontPage} />
        </div>
      </React.Fragment>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      mode: state.session.mode,
      reportConfigs: state.reports.reportConfigs,
      isMobileResolution: state.app.isMobileResolution,
      isWebView: state.app.isWebView,
    }),
    {
      showNotification,
      openModal,
      closeModal,
      saveReportConfigs,
      addPollInstance,
    },
  ),
  rTracking(() => window.rzpQ.component('PaymentPagesContainer')),
)(PaymentPagesV3Entity);

function noop() {}
