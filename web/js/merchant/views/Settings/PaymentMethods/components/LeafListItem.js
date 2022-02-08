import React from 'react';
import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import PropTypes from 'prop-types';

import Popover, { PopoverBody } from 'common/ui/Popover';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  createMerchantInstrumentRequest,
  cancelMerchantInstrumentRequest,
  fetchMerchantInstruments,
  fetchRequestedInstruments,
  getIirDiscrepancies,
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
  ACTIVATED,
  ACCOUNT_LINKABLE,
  REJECTED,
  REQUESTABLE,
  CANCELLED,
  GREYED,
} from '../constants';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { RequestedStatus } from './InstrumentStatuses/RequestedStatus';
import RejectedAndActionRequired from './InstrumentStatuses/RejectedAndActionRequired';

class LeafListItem extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  state = {
    loading: false,
    isImageLoaded: false,
  };

  tracker = (objectName, actionName, screen, properties) => {
    return analyticsTrack({
      objectName,
      actionName,
      screen,
      properties: {
        location: 'Payment Methods',
        ...properties,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  handleDrawerModal = (type) => {
    const { openModal: open, closeModal: close } = this.props;
    return type === 'open'
      ? open({
          component: <DetailsDrawer />,
        })
      : close({
          component: <DetailsDrawer />,
        });
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

    this.tracker('instrument', 'requested', 'settings', {
      instrumentName: instrument.name,
      method: leafInstrument.name,
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
            . Processing the request roughly takes {instrumentsTat && instrumentsTat[requestSlug]}{' '}
            working days.
            <br /> <br />
          </div>
        ),
        affirmativeLabel: 'Confirm',
        affirmativePendingLabel: 'Requesting...',
        abortLabel: 'Cancel',
        action: () => {
          this.tracker('instrument request confirmation popup', 'clicked', 'settings', {
            actionName: 'confirm',
            instrumentName: instrument.name,
            method: leafInstrument.name,
          });
          return this.props
            .createMerchantInstrumentRequest(requestSlug)
            .then(() =>
              this.tracker('instrument request', 'result', 'settings', {
                instrumentName: instrument.name,
                method: leafInstrument.name,
                status: 'Success',
              }),
            )
            .catch(({ errors }) => {
              this.props.showNotification({
                type: 'error',
                message: errors[0],
              });
              this.tracker('instrument request', 'result', 'settings', {
                instrumentName: instrument.name,
                method: leafInstrument.name,
                status: 'Failure',
                failureReason: errors[0],
              });
            })
            .finally(() => this.setState({ loading: false }));
        },
        abort: () => {
          this.setState({ loading: false });
          this.tracker('instrument request confirmation popup', 'clicked', 'settings', {
            actionName: 'cancel',
            instrumentName: instrument.name,
            method: leafInstrument.name,
          });
        },
      })
      .catch(() => {});
  };

  handleCancelRequest = (instrument) => {
    const { leafInstrument } = this.props;
    this.tracker('instrument', 'cancelled', 'settings', {
      instrumentName: instrument.name,
      method: leafInstrument.name,
    });
    this.context
      .confirm({
        header: 'Are you sure?',
        message: `Are you certain you want to cancel request for ${instrument.name}`,
        affirmativeLabel: 'Yes',
        abortLabel: 'No',
        action: () => {
          this.tracker('instrument cancel confirmation popup', 'clicked', 'settings', {
            actionName: 'Yes',
            instrumentName: instrument.name,
            method: leafInstrument.name,
          });
          this.props
            .cancelMerchantInstrumentRequest(instrument.merchant_instrument_request_id)
            .then((d) => {
              if (d.success) {
                this.props.showNotification({
                  type: 'success',
                  message: `Request for ${instrument.name} cancelled successfully`,
                });
                this.tracker('instrument cancel', 'result', 'settings', {
                  instrumentName: instrument.name,
                  method: leafInstrument.name,
                  status: 'Success',
                });
              }
            })
            .catch(({ errors }) => {
              this.props.showNotification({
                type: 'error',
                message: errors[0],
              });
              this.tracker('instrument cancel', 'result', 'settings', {
                instrumentName: instrument.name,
                method: leafInstrument.name,
                status: 'Failure',
                failureReason: errors[0],
              });
            });
        },
        abort: () => {
          this.tracker('instrument cancel confirmation popup', 'clicked', 'settings', {
            actionName: 'No',
            instrumentName: instrument.name,
            method: leafInstrument.name,
          });
        },
      })
      .catch(() => {});
  };

  handleRaiseRequest = (instrument) => {
    this.tracker('Raise Request from Instrument Dashboard', 'clicked', 'settings', {
      actionName: 'No',
      instrumentName: instrument.name,
      status: instrument.status,
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
      activated_action_required: 'Payment method active on your checkout',
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
            {instrument.status === REJECTED && (
              <button className="btn btn-link" onClick={() => this.handleRaiseRequest(instrument)}>
                Raise Request
              </button>
            )}
            {instrument.status === REQUESTED && (
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
        <RequestedStatus instrument={instrument} tat={instrumentsTat} />

        {[REJECTED, ACTION_REQUIRED].includes(instrument.status) && (
          <RejectedAndActionRequired instrument={instrument} />
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
  discrepancyCategories: state.instrumentRequests.discrepancyCategories,
  merchantDiscrepancies: state.instrumentRequests.merchantDiscrepancies,
  user: state.session.user,
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
      getIirDiscrepancies,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(LeafListItem);
