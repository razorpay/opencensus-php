import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import moment from 'moment';

import Popover, { PopoverBody } from 'common/ui/Popover';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  createMerchantInstrumentRequest,
  cancelMerchantInstrumentRequest,
  fetchMerchantInstruments,
  fetchRequestedInstruments,
  setIntrument,
} from 'merchant/reducers/instrumentRequests';

import { getIcon } from './InstrumentIcons';
import PaytmWalletIntegration from './Modals/PaytmWallet/PaytmWalletIntegration';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { DetailsDrawer } from './Modals/PaytmWallet/DetailsDrawer';
import { bindActionCreators } from 'redux';
import {
  ACTION_REQUIRED,
  REQUESTED,
  PENDING,
  ACTIVATED,
  ACCOUNT_LINKABLE,
  REJECTED,
  REQUESTABLE,
  CANCELLED,
} from '../constants';

class LeafListItem extends React.Component {
  constructor(props) {
    super(props);
  }
  static contextTypes = {
    confirm: PropTypes.func,
  };
  state = {
    loading: false,
    isImageLoaded: false,
  };

  handleDrawerModal = (type) => {
    if (type === 'open') {
      return this.props.openModal({
        component: <DetailsDrawer />,
      });
    } else {
      return this.props.closeModal({
        component: <DetailsDrawer />,
      });
    }
  };

  handlePaytmWalletIntegration = (step, status) => {
    return this.props.openModal({
      component: (
        <PaytmWalletIntegration
          fetchInstrumentStatus={async () => {
            try {
              return window.location.reload();
            } catch (err) {
              showNotification({
                type: 'error',
                message: errors[0],
              });
            }
          }}
          step={step}
          status={status}
          openDrawer={() => this.handleDrawerModal('open')}
          closeDrawer={() => this.handleDrawerModal('close')}
          closeModal={() => this.props.closeModal()}
        />
      ),
      className: 'checkout-modal',
    });
  };

  handleCreateRequest = () => {
    this.setState({ loading: true });
    let { instrument, intermediateInstrument, leafInstrument, instrumentsTat } = this.props;
    let requestSlug = `pg.${intermediateInstrument && intermediateInstrument.slug}.${
      leafInstrument && leafInstrument.slug
    }.${instrument.slug}`.replace(/\.null|\.undefined/g, '');

    analyticsTrack({
      objectName: 'instrument',
      actionName: 'requested',
      screen: 'settings',
      properties: {
        location: 'Payment Methods',
        instrumentName: instrument.name,
        method: leafInstrument.name,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    this.context
      .confirm({
        header: 'Confirmation',
        message: () => (
          <div style={{ marginBottom: '-5px' }}>
            This instrument will be enabled for you using &nbsp;
            <span class="toggler-btn">
              <a href="https://razorpay.com/pricing/" target="_blank" rel="noreferrer">
                Standard Pricing <i class="i i-external-link" style={{ marginLeft: '2px' }} />
              </a>
            </span>
            . Processing the request roughly takes {instrumentsTat[requestSlug]} working days.
            <br /> <br />
          </div>
        ),
        affirmativeLabel: 'Confirm',
        affirmativePendingLabel: 'Requesting...',
        abortLabel: 'Cancel',
        action: () => {
          analyticsTrack({
            objectName: 'instrument request confirmation popup',
            actionName: 'clicked',
            screen: 'settings',
            properties: {
              location: 'Payment Methods',
              actionName: 'confirm',
              instrumentName: instrument.name,
              method: leafInstrument.name,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          return this.props
            .createMerchantInstrumentRequest(requestSlug)
            .then(() =>
              analyticsTrack({
                objectName: 'instrument request',
                actionName: 'result',
                screen: 'settings',
                properties: {
                  location: 'Payment Methods',
                  instrumentName: instrument.name,
                  method: leafInstrument.name,
                  status: 'Success',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              }),
            )
            .catch(({ errors }) => {
              this.props.showNotification({
                type: 'error',
                message: errors[0],
              });
              analyticsTrack({
                objectName: 'instrument request',
                actionName: 'result',
                screen: 'settings',
                properties: {
                  location: 'Payment Methods',
                  instrumentName: instrument.name,
                  method: leafInstrument.name,
                  status: 'Failure',
                  failureReason: errors[0],
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
            })
            .finally(() => this.setState({ loading: false }));
        },
        abort: () => {
          this.setState({ loading: false });
          analyticsTrack({
            objectName: 'instrument request confirmation popup',
            actionName: 'clicked',
            screen: 'settings',
            properties: {
              location: 'Payment Methods',
              actionName: 'cancel',
              instrumentName: instrument.name,
              method: leafInstrument.name,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        },
      })
      .catch((e) => {});
  };

  handleCancelRequest = (instrument) => {
    const { leafInstrument } = this.props;
    analyticsTrack({
      objectName: 'instrument',
      actionName: 'cancelled',
      screen: 'settings',
      properties: {
        location: 'Payment Methods',
        instrumentName: instrument.name,
        method: leafInstrument.name,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.context
      .confirm({
        header: 'Are you sure?',
        message: `Are you certain you want to cancel request for ${instrument.name}`,
        affirmativeLabel: 'Yes',
        abortLabel: 'No',
        action: () => {
          analyticsTrack({
            objectName: 'instrument cancel confirmation popup',
            actionName: 'clicked',
            screen: 'settings',
            properties: {
              location: 'Payment Methods',
              actionName: 'Yes',
              instrumentName: instrument.name,
              method: leafInstrument.name,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          this.props
            .cancelMerchantInstrumentRequest(instrument.merchant_instrument_request_id)
            .then((d) => {
              if (d.success) {
                this.props.showNotification({
                  type: 'success',
                  message: `Request for ${instrument.name} cancelled successfully`,
                });
                analyticsTrack({
                  objectName: 'instrument cancel',
                  actionName: 'result',
                  screen: 'settings',
                  properties: {
                    location: 'Payment Methods',
                    instrumentName: instrument.name,
                    method: leafInstrument.name,
                    status: 'Success',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              }
            })
            .catch(({ errors }) => {
              this.props.showNotification({
                type: 'error',
                message: errors[0],
              });
              analyticsTrack({
                objectName: 'instrument cancel',
                actionName: 'result',
                screen: 'settings',
                properties: {
                  location: 'Payment Methods',
                  instrumentName: instrument.name,
                  method: leafInstrument.name,
                  status: 'Failure',
                  failureReason: errors[0],
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
            });
        },
        abort: () => {
          analyticsTrack({
            objectName: 'instrument cancel confirmation popup',
            actionName: 'clicked',
            screen: 'settings',
            properties: {
              location: 'Payment Methods',
              actionName: 'No',
              instrumentName: instrument.name,
              method: leafInstrument.name,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        },
      })
      .catch((e) => {});
  };

  handleRaiseRequest = () => {
    const rzpTicketSystem = window.rzpTicketSystem;
    rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);
    rzpTicketSystem.openModal('#ticket');
    setTimeout(() => {
      rzpTicketSystem.modal.next();
    }, 0);
  };

  render() {
    let {
      instrument,
      intermediateInstrument,
      leafInstrument: { actionItems },
      instrumentsTat,
    } = this.props;
    let ctaClass = {
      Request: 'btn btn-primary',
      account_linkable: 'btn btn-primary',
      requestable: 'btn btn-primary',
      activated: 'activated status',
      requested: 'requested status',
      pending: 'pending status',
      rejected: 'rejected status',
      action_required: 'action-required status',
    };

    let getListClass = (status, path) => {
      if ([ACTION_REQUIRED, REJECTED].includes(status)) {
        return 'action-required-list-item';
      } else if ((status === ACTIVATED && path === 'pg.wallet.paytm') || status === REQUESTED) {
        return 'list-item-has-description';
      } else if (instrument.path === 'pg.upi.google_pay') {
        return 'list-item-has-long-description';
      } else {
        return 'list-item';
      }
    };

    let statusPopoverText = {
      activated: 'Payment method active on your checkout',
      requested: 'Payment method has been requested',
      pending: 'Your request has been forwarded for approval',
      rejected: 'Your request has been rejected',
      action_required: 'Action required on your end to complete the process',
    };
    const displayName = (name) => {
      const displayTextStyle = {
        fontWeight: '500',
        fontSize: '14px',
        lineHeight: '17px',
        color: '#5D666D',
      };
      if (
        (intermediateInstrument &&
          !['cards', 'netbanking'].includes(intermediateInstrument.slug)) ||
        !intermediateInstrument
      ) {
        return <strong>{name}</strong>;
      } else {
        return <p style={displayTextStyle}>{name}</p>;
      }
    };
    return (
      <li class={getListClass(instrument.status, instrument.path)}>
        <div>
          {instrument.icon && (
            <div class="icon">
              <img
                src={getIcon(instrument.icon)}
                alt={instrument.name}
                width="30px"
                height="30px"
                onLoad={() => this.setState({ isImageLoaded: true })}
                style={{
                  display: `${this.state.isImageLoaded ? 'initial' : 'none'}`,
                }}
              />
              {!this.state.isImageLoaded && (
                <div class="flex">
                  <p className="PlaceholderLoader" />
                </div>
              )}
            </div>
          )}
          <div class="detail raise-request">
            <div>
              {displayName(instrument.name)}
              {/* {actionItems && Object.keys(actionItems).includes(instrument.path) ? (
                <span>
                  <span class="notify-badge">1</span>
                  <Popover align="bottom" theme="dark">
                    <PopoverBody>
                      <div style={{ textAlign: 'left' }}>
                        item requires user action. Please complete your activation form.
                      </div>
                    </PopoverBody>
                  </Popover>
                </span>
              ) : null} */}
              {instrument.description && <p>{instrument.description}</p>}
            </div>
            {[REJECTED, ACTION_REQUIRED].includes(instrument.status) && (
              <button class="btn btn-link" onClick={this.handleRaiseRequest}>
                Raise Request
              </button>
            )}
            {![
              PENDING,
              ACTIVATED,
              REJECTED,
              ACTION_REQUIRED,
              REQUESTABLE,
              ACCOUNT_LINKABLE,
              CANCELLED,
            ].includes(instrument.status) && (
              <button class="btn btn-link" onClick={() => this.handleCancelRequest(instrument)}>
                Cancel
              </button>
            )}
          </div>
          <div>
            {instrument.status === ACTIVATED && instrument.path === 'pg.wallet.paytm' && (
              <div className="flex-end instrument-description">
                <div className="instrument-description-container">
                  <div className="detail">
                    <strong>Live Mode</strong>
                    <div class="activated status" style={{ marginRight: '0' }}>
                      {instrument.status.replace('_', ' ')}
                      <Popover align="bottom" theme="dark">
                        <PopoverBody>
                          <div style={{ textAlign: 'left', textTransform: 'none' }}>
                            {statusPopoverText[instrument.status]}
                          </div>
                        </PopoverBody>
                      </Popover>
                    </div>
                  </div>
                  <p id="status">
                    Paytm Wallet is enabled on Live Mode, customers can transact with Paytm wallet.
                    To view/edit your Paytm Production API Credentials,{' '}
                    <a onClick={() => this.handlePaytmWalletIntegration(2, instrument.status)}>
                      click here
                    </a>{' '}
                    .
                  </p>
                </div>
              </div>
            )}
            {[ACCOUNT_LINKABLE].includes(instrument.status) && (
              <div className="flex-end">
                <button
                  class="btn btn-primary ml-5"
                  disabled={this.state.loading}
                  onClick={() => this.handlePaytmWalletIntegration(1, instrument.status)}
                >
                  {this.state.loading ? 'Loading..' : 'Link Account'}
                </button>
              </div>
            )}
            {[REQUESTABLE, CANCELLED].includes(instrument.status) && (
              <div className="flex-end">
                <button
                  class="btn btn-primary ml-5"
                  disabled={this.state.loading}
                  onClick={this.handleCreateRequest}
                >
                  {this.state.loading ? 'Requesting..' : 'Request'}
                </button>
              </div>
            )}
            {![REQUESTABLE, CANCELLED, ACCOUNT_LINKABLE].includes(instrument.status) &&
              instrument.path !== 'pg.wallet.paytm' && (
                <div className="flex-end">
                  <div class={ctaClass[instrument.status]}>
                    <>
                      {instrument.status.replace('_', ' ')}
                      <Popover align="bottom" theme="dark">
                        <PopoverBody>
                          <div style={{ textAlign: 'left', textTransform: 'none' }}>
                            {statusPopoverText[instrument.status]}
                          </div>
                        </PopoverBody>
                      </Popover>
                    </>
                  </div>
                </div>
              )}
          </div>
        </div>
        {/* Requested state */}
        {instrument.status === REQUESTED && (
          <div className="flex-end instrument-description">
            <div
              className="instrument-description-container"
              style={{
                display: 'flex',
                flexGrow: 1,
                flexDirection: 'row',
                alignItems: 'center',
                justifyContent: 'space-evenly',
              }}
            >
              <i className="i i-info-outline"></i>
              <p style={{ fontSize: '14px' }}>
                Estimated date of enablement:{' '}
                <strong>
                  {instrumentsTat &&
                    moment
                      .unix(instrument.created_at)
                      .add(instrumentsTat[instrument.path], 'days')
                      .format('Do MMMM YYYY')}
                  {instrumentsTat &&
                    moment().diff(
                      moment
                        .unix(instrument.created_at)
                        .add(instrumentsTat[instrument.path], 'days'),
                    ) > 0 &&
                    '*'}
                </strong>{' '}
              </p>
            </div>
            {instrumentsTat &&
              moment().diff(
                moment.unix(instrument.created_at).add(instrumentsTat[instrument.path], 'days'),
              ) > 0 && (
                <div>
                  <p style={{ color: 'rgba(0, 0, 0, 0.38)', fontSize: '14px', paddingTop: '8px' }}>
                    * Sorry for the inconvenience, the request is taking longer than usual.
                  </p>
                </div>
              )}
          </div>
        )}
        {[ACTION_REQUIRED, REJECTED].includes(instrument.status) && (
          <>
            <div class="comment" title={instrument.comment}>
              <i class="i i-info-outline" />
              <p>{instrument.comment || 'No comments available'}</p>
            </div>

            {/* <p style={{ margin: '5px 20px' }}>
              Please complete your{' '}
              <span>
                {' '}
                <Link to="/activation" style={{ textDecoration: 'underline' }}>
                  Activation Form
                </Link>
              </span>{' '}
              to re-submit your request.
            </p> */}
          </>
        )}
      </li>
    );
  }
}

const mapStateToProps = (state) => ({
  intermediateInstrument: state.instrumentRequests.intermediateInstrument,
  leafInstrument: state.instrumentRequests.leafInstrument,
  userActivationStatus: state.session.user.activation_status,
  instrumentsTat: state.instrumentRequests.instrumentsTat,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      createMerchantInstrumentRequest,
      cancelMerchantInstrumentRequest,
      showNotification,
      openModal,
      closeModal,
      fetchMerchantInstruments,
      fetchRequestedInstruments,
      setIntrument,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(LeafListItem);
