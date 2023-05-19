import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { bindActionCreators } from 'redux';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

import { Button } from '@razorpay/blade/components';

import PropTypes from 'prop-types';

import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import {
  createMerchantInstrumentRequest,
  cancelMerchantInstrumentRequest,
  reinitiateMerchantInstrumentRequest,
  fetchMerchantInstruments,
  fetchRequestedInstruments,
  getIirDiscrepancies,
  setInstrument,
} from 'merchant/reducers/instrumentRequests';

import { getIcon } from 'merchant/views/Settings/PaymentMethods/components/InstrumentIcons';
import { getIcon as getPaymentMethodIcon } from 'merchant/views/Settings/PaymentMethods/components/paymentMethodIcons';
import PaytmWalletIntegration from 'merchant/views/Settings/PaymentMethods/components/Modals/PaytmWallet/PaytmWalletIntegration';
import { DetailsDrawer } from 'merchant/views/Settings/PaymentMethods/components/Modals/PaytmWallet/DetailsDrawer';
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
  statusClass,
  statusPopoverText,
  additionalDetailsStatus,
  DISABLED_INSTRUMENT,
} from 'merchant/views/Settings/PaymentMethods/constants';
import { RequestedStatus } from 'merchant/views/Settings/PaymentMethods/components/InstrumentStatuses/RequestedStatus';
import RejectedAndActionRequired from 'merchant/views/Settings/PaymentMethods/components/InstrumentStatuses/RejectedAndActionRequired';
import AdditionalDetails from 'merchant/views/Settings/PaymentMethods/components/InstrumentStatuses/AdditionalDetails';
import MissingInfoModal from 'merchant/views/Settings/PaymentMethods/components/MissingInfoModal';

import ConfirmBoxContext from 'merchant/views/Settings/PaymentMethods/components/ConfimBoxContent';
import { displayName, getListClass } from './utils';

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

  createRequestAction = (instrument, leafInstrument, requestSlug) => {
    this.tracker('instrument request confirmation popup', 'clicked', 'settings', {
      actionName: 'confirm',
      instrumentName: instrument.name,
      method: leafInstrument.name,
    });
    selfServeTrackInitiate({
      selfServeAction: 'Instrument Requested',
      page: 'Payment-Methods',
      screen: 'Settings',
    });
    return this.props
      .createMerchantInstrumentRequest(requestSlug)
      .then(() => {
        this.tracker('instrument request', 'result', 'settings', {
          instrumentName: instrument.name,
          method: leafInstrument.name,
          status: 'Success',
        });
        selfServeTrackSuccess({
          selfServeAction: 'Instrument Requested',
          page: 'Payment-Methods',
          screen: 'Settings',
        });
      })
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: `${instrument.name} requested successfully`,
        });
        this.props.history.replace('/');
        setTimeout(() => {
          this.props.history.replace('/payment-methods');
        }, 10);
      })
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
        this.props.closeModal();
      })
      .finally(() => {
        this.setState({ loading: false });
        this.context.confirm({
          forceClose: true,
        });
      });
  };

  handleOnCancelRequest = (instrument, leafInstrument) => {
    this.setState({ loading: false });
    this.tracker('instrument request confirmation popup', 'clicked', 'settings', {
      actionName: 'cancel',
      instrumentName: instrument.name,
      method: leafInstrument.name,
    });
    this.context.confirm({
      forceClose: true,
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

    if (instrument.collect_info) {
      return this.createRequestAction(instrument, leafInstrument, requestSlug);
    }

    this.context
      .confirm({
        header: 'Confirmation',
        message: (
          <ConfirmBoxContext
            numberOfDays={instrumentsTat && instrumentsTat[requestSlug]}
            onRequestAbort={() => this.handleOnCancelRequest(instrument, leafInstrument)}
            onRequest={() => {
              this.createRequestAction(instrument, leafInstrument, requestSlug);
            }}
          />
        ),
        hideActions: true,
      })
      .catch(() => {});
    return true;
  };

  handleMissingInfoModal = () => {
    const { instrument, leafInstrument, instrumentsTat } = this.props;

    this.tracker('instrument', 'requested', 'settings', {
      instrumentName: instrument.name,
      method: leafInstrument.name,
    });
    this.props.openModal({
      component: (
        <MissingInfoModal
          instrument={instrument}
          tat={instrumentsTat[instrument.path]}
          onCloseClick={this.props.closeModal}
          createRequestAction={() =>
            this.createRequestAction(instrument, leafInstrument, instrument.path)
          }
          merchantId={this.props?.user?.id}
        />
      ),
      className: 'missinginfo-modal',
    });
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
            .then(() => {
              this.props.history.replace('/');
              return setTimeout(() => {
                this.props.history.replace('/payment-methods');
              }, 10);
            })
            .catch((err) => {
              this.props.showNotification({
                type: 'error',
                message: err?.errors[0],
              });
              this.tracker('instrument cancel', 'result', 'settings', {
                instrumentName: instrument.name,
                method: leafInstrument.name,
                status: 'Failure',
                failureReason: err?.errors[0],
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

  handleReinitiateRequest = (instrument) => {
    const { leafInstrument } = this.props;
    this.tracker('instrument', 'reinitiated', 'settings', {
      instrumentName: instrument.name,
      method: leafInstrument.name,
    });
    this.context
      .confirm({
        header: 'Are you sure?',
        message: `Are you certain you want to reinitiate request for ${instrument.name}?`,
        affirmativeLabel: 'Yes',
        abortLabel: 'No',
        action: () => {
          this.tracker('instrument reinitiate confirmation popup', 'clicked', 'settings', {
            actionName: 'Yes',
            instrumentName: instrument.name,
            method: leafInstrument.name,
          });
          this.props
            .reinitiateMerchantInstrumentRequest(instrument.merchant_instrument_request_id)
            .then((d) => {
              if (d.success) {
                this.props.showNotification({
                  type: 'success',
                  message: `Request for ${instrument.name} reinitiated successfully`,
                });
                this.tracker('instrument reinitiate', 'result', 'settings', {
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
              this.tracker('instrument reinitiate', 'result', 'settings', {
                instrumentName: instrument.name,
                method: leafInstrument.name,
                status: 'Failure',
                failureReason: errors[0],
              });
            });
        },
        abort: () => {
          this.tracker('instrument reinitiate confirmation popup', 'clicked', 'settings', {
            actionName: 'No',
            instrumentName: instrument.name,
            method: leafInstrument.name,
          });
        },
      })
      .catch(() => {});
  };

  handleRequest = () => {
    const { instrument } = this.props;

    if (instrument.collect_info) {
      this.handleMissingInfoModal();
    } else {
      this.handleCreateRequest();
    }
  };

  render() {
    const { instrument, intermediateInstrument, instrumentsTat, user } = this.props;

    const isAffordabilityOnboardingActive = user.isShowSegregatedCreditEmi;

    // If affordability Onboarding experiment only then we need to show the credit emi changes
    if (instrument.slug === 'credit' && isAffordabilityOnboardingActive)
      instrument.name = 'Other banks';
    if (instrument.slug === 'credit.sbi' && !isAffordabilityOnboardingActive) {
      return null;
    }

    const shouldReinitiateRequest =
      instrument.status === ACTION_REQUIRED &&
      !instrument?.should_show_smart_dashboard_flow &&
      instrument?.should_show_reinitiate_button;
    const isMissingInfo = instrument?.capture_info_before_mir;
    const isGrayed = instrument.status === GREYED && instrument.fade_comment;
    const instrumentParent = instrument?.path?.split('.')[1];
    const shouldShowGSTMessage =
      ['2', '11'].includes(user?.business_type) &&
      isMissingInfo?.some((field) => field.name === 'merchant_details|gstin');

    const isInstrumentDisabled =
      instrument.status === GREYED || DISABLED_INSTRUMENT.includes(instrument.name);

    return (
      <li className={getListClass({ status: instrument.status, path: instrument.path, user })}>
        <div>
          {instrument.slug === 'credit' && isAffordabilityOnboardingActive ? (
            <div className="icon">{getPaymentMethodIcon('card')}</div>
          ) : null}
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
              {displayName({ name: instrument.name, intermediateInstrument })}
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
              {instrument.description && isAffordabilityOnboardingActive ? (
                <p>
                  {instrument.description}
                  {instrument.docLink && (
                    <a href={instrument.docLink} target="_blank" rel="noopener noreferrer">
                      &nbsp; &amp; more
                    </a>
                  )}
                </p>
              ) : null}
            </div>
            {shouldReinitiateRequest && (
              <button
                className="btn btn-link"
                onClick={() => this.handleReinitiateRequest(instrument)}
              >
                Reinitiate request
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
            {!user.isInstrumentRequestHidden && instrument.status === ACCOUNT_LINKABLE && (
              <div className="flex-end">
                <Button
                  testID="pm-link-account-cta"
                  isDisabled={this.state.loading}
                  onClick={() => this.handlePaytmWalletIntegration(1, instrument.status)}
                >
                  {this.state.loading ? 'Loading..' : 'Link Account'}
                </Button>
              </div>
            )}
            {!user.isInstrumentRequestHidden &&
              [REQUESTABLE, CANCELLED, GREYED].includes(instrument.status) && (
                <div className="flex-end">
                  <Button
                    testID="pm-request-cta"
                    isDisabled={isInstrumentDisabled}
                    isLoading={this.state.loading}
                    onClick={this.handleRequest}
                  >
                    Request
                  </Button>
                  {isGrayed && (
                    <Popover align="bottom" theme="dark">
                      <PopoverBody>{instrument?.fade_comment || ''}</PopoverBody>
                    </Popover>
                  )}
                </div>
              )}
            {![REQUESTABLE, CANCELLED, GREYED, ACCOUNT_LINKABLE].includes(instrument.status) &&
              instrument.path !== 'pg.wallet.paytm' && (
                <div className="flex-end">
                  <div className={statusClass[instrument.status]}>
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
        {isMissingInfo && (
          <details>
            <p>
              The following fields need to be updated to request {instrument?.name} for{' '}
              {instrumentParent} payments.
              {shouldShowGSTMessage &&
                'Unregistered businesses are not allowed to update GST details'}
            </p>
            {isMissingInfo?.map(
              ({ display_name, url, status_identifier, current_value, status }, key) => {
                const displayStatus = additionalDetailsStatus[status] || '';
                return (
                  <AdditionalDetails
                    key={key}
                    displayName={display_name}
                    currentValue={current_value}
                    statusIdentifier={status_identifier}
                    url={url}
                    status={displayStatus}
                  />
                );
              },
            )}
            <summary>
              <p>
                Additional Details Required <i className="i i-chevron-up" />
              </p>
            </summary>
          </details>
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
      reinitiateMerchantInstrumentRequest,
      showNotification,
      openModal,
      closeModal,
      fetchMerchantInstruments,
      fetchRequestedInstruments,
      getIirDiscrepancies,
      setInstrument,
    },
    dispatch,
  );
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(LeafListItem));
