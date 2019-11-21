import { Link } from 'react-router-dom';
import { connect } from 'react-redux';

import { updatePPInReduxList } from 'merchant/reducers/invoices/list';
import { classList } from 'common/utils/rzp-utils';
import TestModeBanner from 'merchant/containers/TestModeBanner';

import { sendLink } from '../../model';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import Definition from 'common/ui/Definition';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Time from 'common/ui/Time';
import Amount from 'common/ui/Amount';
import CopyLink from 'merchant/components/CopyLink';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { addPollInstance, saveReportConfigs } from 'merchant/reducers/reports';
import { showNotification } from 'merchant_common/reducers/notifications';
import { trackDetailViewEdits, trackShareActions } from '../../ga';
import { exportReportCSV } from '../../model';

import EditStock from 'merchant/views/PaymentPages/PaymentPages/components/EditStock';

import {
  EditExpiry,
  EditNotes,
} from 'merchant/containers/PaymentLinks/Edit/index';
import ShareView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Share';
import PPEmbedButtonView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/EmbedButton';

import PaymentsList from './PaymentsList';

import Button from 'common/new-ui/Button';
import Tooltip from 'common/ui/Tooltip';

// import mockPaymentPage from '../../Wysiwyg/data-mock';

/* Human readable reason to be displayed */
const inActiveStatusReasonMap = {
  completed: 'One or more items are out of stock',
  expired: 'The link is expired',
  deactivated: 'You manually deactivated the link',
};

@connect(
  state => ({
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
  }
)
export default class PaymentPagesV3Entity extends React.Component {
  state = { detailsCollapse: true, isExportInProgress: false };

  componentDidMount() {
    if (!this.props.reportConfigs) {
      this.props.saveReportConfigs();
    }
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

  downloadReport = () => {
    const { user, paymentPageEntity, reportConfigs } = this.props;
    let configId;

    if (!reportConfigs.length) {
      return;
    }

    for (let i = 0; i < reportConfigs.length; i++) {
      const config = reportConfigs[i];
      if (config.type === 'payment_links') {
        configId = config.id;
        break;
      }
    }

    const promise = exportReportCSV(
      user,
      paymentPageEntity,
      configId,
      this.saveLongPollInstances
    );

    if (promise && promise.then) {
      this.setState({
        isExportInProgress: true,
      });

      this.props.showNotification({
        type: 'success',
        message: 'Your report will download shortly',
      });

      promise.then(data => {
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
  };

  openEmbedButtonView = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <PPEmbedButtonView
          handleClose={this.props.closeModal}
          trackerFn={function() {}}
          url={this.props.paymentPageEntity.short_url}
          color={this.props.merchantColor}
        />
      ),
    });
  };

  openShareView = () => {
    const { paymentPageEntity } = this.props;
    trackDetailViewEdits('Click Share');
    this.props.openModal({
      size: 'small',
      component: (
        <ShareView
          handleClose={this.props.closeModal}
          openModal={this.props.openModal}
          handleAction={sendLink.bind(null, paymentPageEntity.id)}
          showNotification={this.props.showNotification}
          url={paymentPageEntity.short_url}
          title={paymentPageEntity.title}
          description={paymentPageEntity.description}
          trackerFn={trackShareActions}
          openEmbedButton={this.openEmbedButtonView}
        />
      ),
    });
  };

  render() {
    let {
      createdByUser,
      paymentPageEntity,
      editPaymentPage,
      toggleManualActivation,
      reActivateLink,
      reportConfigs,
      isMobileResolution,
    } = this.props;

    // paymentPageEntity = mockPaymentPage;

    const isRoleAllowedEdit = this.props.user.isAllowedEdit('payment_pages');

    let status = paymentPageEntity.status;
    let statusReason = paymentPageEntity.status_reason;

    const isActive = status === 'active';
    const isExpired = !isActive && statusReason.toLowerCase() === 'expired';

    const isCompleted = !isActive && statusReason.toLowerCase() === 'completed';

    const isSmsOrEmailSent =
      paymentPageEntity.sms_status === 'sent' ||
      paymentPageEntity.email_status === 'sent';

    return (
      <React.Fragment>
        <div
          class={classList(
            'content-sm txn-details Entity--paymentpage Entity--paymentpage-v2 Entity--paymentpage-v3',
            this.state.detailsCollapse && 'Entity--paymentpage-collapse'
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
              <div class="btn-toolbar pull-right">
                {isRoleAllowedEdit && (
                  <Link
                    class="btn Button--primary--invert btn-sm"
                    to={`/paymentpages/new?duplicate_id=${
                      paymentPageEntity.id
                    }`}
                  >
                    {isMobileResolution ? (
                      <i class="i i-copy" />
                    ) : (
                      'Duplicate Page'
                    )}
                  </Link>
                )}

                {isRoleAllowedEdit && (
                  <Link
                    class="btn Button--primary--invert btn-sm m-l"
                    to={`/paymentpages/${paymentPageEntity.id}/edit`}
                  >
                    {isMobileResolution ? <i class="i i-edit" /> : 'Edit'}
                  </Link>
                )}

                {isRoleAllowedEdit &&
                  isActive && (
                    <button
                      class="btn btn-primary btn-sm"
                      onClick={this.openShareView}
                    >
                      {isMobileResolution ? <i class="i i-share" /> : 'Share'}
                    </button>
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
                          onClick={
                            isActive ? toggleManualActivation : reActivateLink
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

                <EntityDetailRow
                  label="Payment Page ID"
                  value={paymentPageEntity.id}
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
                      trackerFn={trackDetailViewEdits}
                      isRoleAllowedEdit={isRoleAllowedEdit}
                    />
                  )}
                />
              </div>

              <div class="item-details">
                <table>
                  <tbody>
                    {paymentPageEntity.payment_page_items.map((pi, ix) => (
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
                              currency={paymentPageEntity.currency}
                            />
                          </div>
                        </td>
                        <td>
                          <div>
                            <div class="title">Price</div>
                            <Amount
                              value={pi.item.amount}
                              currency={paymentPageEntity.currency}
                            />
                          </div>
                        </td>
                        <td class="item-details-units">
                          <div>
                            <div class="title">Units Sold</div>
                            <EditStock
                              totalStock={pi.stock}
                              quantitySold={pi.quantity_sold}
                              editFn={editPaymentPage}
                              paymentPageItemId={pi.id}
                              trackerFn={trackDetailViewEdits}
                              isRoleAllowedEdit={isRoleAllowedEdit}
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
            class="btn-primary btn-sm panel-collapser"
            onClick={_ =>
              this.setState({ detailsCollapse: !this.state.detailsCollapse })
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

        <div class="content-sm txn-details Entity--paymentpage-v3">
          {this.props.mode === 'test' && <TestModeBanner />}

          <div class="stats">
            <b class="bold">Transactions</b>
            {this.getStatsTable(paymentPageEntity).map((st, ix) => (
              <div key={ix}>
                {st.title}
                <b class="bold">{st.value}</b>
              </div>
            ))}

            <div className="btn-toolbar pull-right">
              <button
                type="button"
                className="btn Button--primary--invert btn-sm"
                onClick={this.downloadReport}
                disabled={this.state.isExportInProgress}
              >
                <i className="i i-download m-r" />
                Export All (CSV)
              </button>
            </div>
          </div>

          <PaymentsList paymentPageId={paymentPageEntity.id} />
        </div>
      </React.Fragment>
    );
  }
}
