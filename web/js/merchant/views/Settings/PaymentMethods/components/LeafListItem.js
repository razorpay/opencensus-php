import React from 'react';
import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import moment from 'moment';

import PropTypes from 'prop-types';

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
  ACTIVATED_ACTION_REQUIRED,
  REQUESTED,
  PENDING,
  ACTIVATED,
  ACCOUNT_LINKABLE,
  REJECTED,
  REQUESTABLE,
  CANCELLED,
  GREYED,
} from '../constants';
import { CreateTicketEmitter } from '../../../TicketSupport/utils';

class LeafListItem extends React.Component {
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
          fetchInstrumentStatus={() => {
            try {
              return window.location.reload();
            } catch (err) {
              return showNotification({
                type: 'error',
                message: err.errors[0],
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
    const { instrument, intermediateInstrument, leafInstrument, instrumentsTat } = this.props;
    const requestSlug = `pg.${intermediateInstrument && intermediateInstrument.slug}.${
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
            <span className="toggler-btn">
              <a href="https://razorpay.com/pricing/" target="_blank" rel="noopener noreferrer">
                Standard Pricing <i className="i i-external-link" style={{ marginLeft: '2px' }} />
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
      .catch(() => {});
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
      .catch(() => {});
  };

  handleRaiseRequest = (status) => {
    analyticsTrack({
      objectName: `Raise Request ${status} Instrument Dashboard`,
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'Payment Methods',
        actionName: 'No',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    if (window.rzpTicketSystem) {
      CreateTicketEmitter.emit('create-ticket', 'tickets');
    }
  };

  render() {
    const { instrument, intermediateInstrument, instrumentsTat } = this.props;
    const ctaClass = {
      Request: 'btn btn-primary',
      account_linkable: 'btn btn-primary',
      requestable: 'btn btn-primary',
      cancelled: 'btn btn-primary',
      activated: 'activated status',
      requested: 'requested status',
      pending: 'pending status',
      rejected: 'rejected status',
      action_required: 'action-required status',
      activated_action_required: 'activated-action-required status',
      greyed: 'btn btn-primary disabled',
    };

    const getListClass = (status, path) => {
      if ([REJECTED, ACTION_REQUIRED].includes(status)) {
        return 'action-required-list-item';
      } else if (status === ACTIVATED_ACTION_REQUIRED) {
        return 'activated-action-required-list-item';
      } else if (status === GREYED) {
        return 'list-item-disabled';
      } else if ((status === ACTIVATED && path === 'pg.wallet.paytm') || status === REQUESTED) {
        return 'list-item-has-description';
      } else if (instrument.path === 'pg.upi.google_pay') {
        return 'list-item-has-long-description';
      } else {
        return 'list-item';
      }
    };

    const statusPopoverText = {
      activated: 'Payment method active on your checkout',
      requested: 'Payment method has been requested',
      pending: 'Your request has been forwarded for approval',
      rejected: 'Your request has been rejected',
      action_required: 'Action required on your end to complete the process',
      activated_action_required: 'We need some more details regarding your request',
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
      <li className={getListClass(instrument.status, instrument.path)}>
        <div>
          {instrument.icon && (
            <div className="icon">
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
                <div className="flex">
                  <p className="PlaceholderLoader" />
                </div>
              )}
            </div>
          )}
          <div className="detail raise-request">
            <div>
              {displayName(instrument.name)}
              {/* {actionItems && Object.keys(actionItems).includes(instrument.path) ? (
                <span>
                  <span className="notify-badge">1</span>
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
              <button
                className="btn btn-link"
                onClick={() => this.handleRaiseRequest(instrument.status)}
              >
                Raise Request
              </button>
            )}
            {![
              PENDING,
              ACTIVATED,
              REJECTED,
              ACTION_REQUIRED,
              ACTIVATED_ACTION_REQUIRED,
              REQUESTABLE,
              GREYED,
              ACCOUNT_LINKABLE,
              CANCELLED,
            ].includes(instrument.status) && (
              <button className="btn btn-link" onClick={() => this.handleCancelRequest(instrument)}>
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
                    <div className="activated status" style={{ marginRight: '0' }}>
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
                  className="btn btn-primary ml-5"
                  disabled={this.state.loading}
                  onClick={() => this.handlePaytmWalletIntegration(1, instrument.status)}
                >
                  {this.state.loading ? 'Loading..' : 'Link Account'}
                </button>
              </div>
            )}
            {[REQUESTABLE, CANCELLED, GREYED].includes(instrument.status) && (
              <div className="flex-end">
                <button
                  className={`${ctaClass[instrument.status]} ml-5`}
                  disabled={this.state.loading || instrument.status === GREYED}
                  onClick={this.handleCreateRequest}
                >
                  {this.state.loading ? 'Requesting..' : 'Request'}
                </button>
                {instrument.status === GREYED && (
                  <Popover align="bottom" theme="dark">
                    <PopoverBody>
                      <div style={{ textAlign: 'left', textTransform: 'none' }}>
                        {instrument.fade_comment}
                      </div>
                    </PopoverBody>
                  </Popover>
                )}
              </div>
            )}
            {![REQUESTABLE, CANCELLED, GREYED, ACCOUNT_LINKABLE].includes(instrument.status) &&
              instrument.path !== 'pg.wallet.paytm' && (
                <div className="flex-end">
                  <div className={ctaClass[instrument.status]}>
                    {instrument.status === ACTIVATED_ACTION_REQUIRED
                      ? ACTIVATED
                      : instrument.status.replace('_', ' ')}
                    <Popover align="bottom" theme="dark">
                      <PopoverBody>
                        <div style={{ textAlign: 'left', textTransform: 'none' }}>
                          {statusPopoverText[instrument.status]}
                        </div>
                      </PopoverBody>
                    </Popover>
                  </div>
                </div>
              )}
          </div>
        </div>
        {/* Requested state */}
        {instrument.status === REQUESTED && (
          <div className="flex-end instrument-description">
            <div className="instrument-description-container">
              <i className="i i-info-outline" />
              <p>
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
        {[REJECTED, ACTION_REQUIRED].includes(instrument.status) && (
          <div className="comment" title={instrument.comment}>
            <i className="i i-info-outline" />
            <p>{instrument.comment || 'No comments available'}</p>
          </div>
        )}
        {/* {instrument.status === ACTIVATED_ACTION_REQUIRED && (
          <div className="comment">
            <details className="description" open>
              <summary className="title">
                <div>
                  <img
                    src="https://cdn.razorpay.com/static/assets/instrument-request/trending.png"
                    alt="Boost Success Rate"
                    width="15px"
                    height="10px"
                  />
                  Boost Success Rate
                </div>
                <div className="toggle">
                  <i className="i i-chevron-down" />
                </div>
              </summary>
              <div className="description">{instrument.comment || 'No comments available'}</div>
              <div className="action">
                <a onClick={this.handleRaiseRequest}>Raise Request</a> to know more.
              </div>
            </details>
          </div>
        )} */}
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
