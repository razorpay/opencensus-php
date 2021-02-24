import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

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
  REQUEST,
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
          return this.props
            .createMerchantInstrumentRequest(requestSlug)
            .catch(({ errors }) => {
              this.props.showNotification({
                type: 'error',
                message: errors[0],
              });
            })
            .finally(() => this.setState({ loading: false }));
        },
        abort: () => {
          this.setState({ loading: false });
        },
      })
      .catch((e) => {});
  };

  handleCancelRequest = (instrument) => {
    this.context
      .confirm({
        header: 'Are you sure?',
        message: `Are you certain you want to cancel request for ${instrument.name}`,
        affirmativeLabel: 'Yes',
        abortLabel: 'No',
        action: () => {
          this.props
            .cancelMerchantInstrumentRequest(instrument.merchant_instrument_request_id)
            .then((d) => {
              if (d.success) {
                this.props.showNotification({
                  type: 'success',
                  message: `Request for ${instrument.name} cancelled successfully`,
                });
              }
            })
            .catch(({ errors }) => {
              this.props.showNotification({
                type: 'error',
                message: errors[0],
              });
            });
        },
        abort: () => {},
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

    let customHeight = instrument.description ? { minHeight: '70px' } : {};

    let getListClass = (status, path) => {
      if ([ACTION_REQUIRED, REJECTED].includes(status)) {
        return 'action-required-list-item';
      } else if (status === ACTIVATED && path === 'pg.wallet.paytm') {
        return 'activated-paytm-list-item';
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
      <li class={getListClass(instrument.status, instrument.path)} style={customHeight}>
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
              {actionItems && Object.keys(actionItems).includes(instrument.path) ? (
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
              ) : null}
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
            ].includes(instrument.status) && (
              <button class="btn btn-link" onClick={() => this.handleCancelRequest(instrument)}>
                Cancel
              </button>
            )}
          </div>
          <div>
            {instrument.status === ACTIVATED && instrument.path === 'pg.wallet.paytm' && (
              <div className="flex-end instrument__paytm-wallet">
                <div className="container">
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
                  class="btn btn-primary mr-25 ml-5"
                  disabled={this.state.loading}
                  onClick={() => this.handlePaytmWalletIntegration(1, instrument.status)}
                >
                  {this.state.loading ? 'Loading..' : 'Link Account'}
                </button>
              </div>
            )}
            {[REQUEST, REQUESTABLE, CANCELLED].includes(instrument.status) && (
              <div
                style={{
                  display: 'flex',
                  justifyContent: 'flex-end',
                  alignItems: 'center',
                }}
              >
                <button
                  class="btn btn-primary mr-25 ml-5"
                  disabled={this.state.loading}
                  onClick={this.handleCreateRequest}
                >
                  {this.state.loading ? 'Requesting..' : 'Request'}
                </button>
              </div>
            )}
            {![REQUEST, REQUESTABLE, CANCELLED, ACCOUNT_LINKABLE].includes(instrument.status) &&
              instrument.path !== 'pg.wallet.paytm' && (
                <div
                  style={{
                    display: 'flex',
                    justifyContent: 'flex-end',
                    alignItems: 'center',
                  }}
                >
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
        {[ACTION_REQUIRED, REJECTED].includes(instrument.status) && (
          <>
            <div class="comment" title={instrument.comment}>
              <i class="i i-info-outline" />
              <p>{instrument.comment || 'No comments available'}</p>
            </div>

            <p style={{ margin: '5px 20px' }}>
              Please complete your{' '}
              <span>
                {' '}
                <Link to="/activation" style={{ textDecoration: 'underline' }}>
                  Activation Form
                </Link>
              </span>{' '}
              to re-submit your request.
            </p>
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
