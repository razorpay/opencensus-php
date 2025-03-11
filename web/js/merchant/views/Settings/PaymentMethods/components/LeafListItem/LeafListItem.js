import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import { bindActionCreators } from 'redux';

import { withSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
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
  clearIntermediateInstrument,
  clearLeafInstrument,
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
  ACTIVATED_ACTION_REQUIRED,
  CC_EMI_SEPARATE_INSTRUMENT,
} from 'merchant/views/Settings/PaymentMethods/constants';
import { RequestedStatus } from 'merchant/views/Settings/PaymentMethods/components/InstrumentStatuses/RequestedStatus';
import RejectedAndActionRequired from 'merchant/views/Settings/PaymentMethods/components/InstrumentStatuses/RejectedAndActionRequired';
import AdditionalDetails from 'merchant/views/Settings/PaymentMethods/components/InstrumentStatuses/AdditionalDetails';
import MissingInfoModal from 'merchant/views/Settings/PaymentMethods/components/MissingInfoModal';

import ConfirmBoxContext from 'merchant/views/Settings/PaymentMethods/components/ConfimBoxContent';
import { getDisabledInstruments } from 'merchant/views/Settings/PaymentMethods/utils';
import { displayName, getListClass } from './utils';
import ScrapperModal from 'merchant/views/Settings/PaymentMethods/components/ScrapperModal';

class LeafListItem extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  state = {
    loading: false,
    isImageLoaded: false,
    isOpen: false,
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
      .then(async () => {
        try {
          /** refetching instrument status for latest data
           *  with web-scrapper other instruments will also get updated for collect_info
           *  hence fetching again
           **/
          await this.props.fetchMerchantInstruments();
          this.props.showNotification({
            type: 'success',
            message: `${instrument.name} requested successfully`,
          });
        } catch (error) {
          this.props.showNotification({
            type: 'error',
            message: 'Failed to fetch latest instrument statuses.',
          });
        }
        this.props.history.replace('/');
        this.props.clearIntermediateInstrument();
        this.props.clearLeafInstrument();
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
    const slugParts = ['pg', intermediateInstrument?.slug, leafInstrument?.slug, instrument?.slug];

    /**
     * .filter(Boolean) // Filter out any empty or falsy values from slugParts
     * .join('.') // Join the filtered parts with a period (.) to create the required slug format (e.g., "cards.domestic.visa")
     * .replace(/\.null|\.undefined/g, '') // Remove occurrences of ".null" and ".undefined" from the slug
     * .replace(/\.meal-card/g, '.cards'); // For sodexo, backend has not introduced a new method, instead it is part of cards.
     *  But in UI we isolate Sodexo out of cards, so converting string from 'pg.meal-card.domestic.sodexo' -> 'pg.cards.domestic.sodexo'
     *  (this conversion helps in retain all redux action update logic same)
     */

    const requestSlug = slugParts
      .filter(Boolean)
      .join('.')
      .replace(/\.null|\.undefined/g, '')
      .replace(/\.meal-card/g, '.cards');

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
            instrumentSlug={instrument?.slug}
            numberOfDays={instrumentsTat && instrumentsTat[requestSlug]}
            onRequestAbort={() => this.handleOnCancelRequest(instrument, leafInstrument)}
            onRequest={() => this.createRequestAction(instrument, leafInstrument, requestSlug)}
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
              this.props.clearIntermediateInstrument();
              this.props.clearLeafInstrument();
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

  showScrapperModal = (isSuccessful = false) => {
    this.setState((state) => {
      return {
        ...state,
        isOpen: !state.isOpen,
        loading: isSuccessful,
      };
    });
    if (isSuccessful) {
      const { instrument, leafInstrument } = this.props;
      this.createRequestAction(instrument, leafInstrument, instrument.path);
    }
  };

  handleScrapperModal = () => {
    const { instrument, leafInstrument } = this.props;

    if (instrument.collect_info) {
      this.tracker('instrument', 'requested', 'web scrapper', {
        instrumentName: instrument.name,
        method: leafInstrument.name,
      });
      this.showScrapperModal();
    } else {
      this.handleCreateRequest();
    }
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
    const { instrument, intermediateInstrument, instrumentsTat, user, leafInstrument, splitz } =
      this.props;
    const { abExperiments } = splitz ?? { abExperiments: { enable_web_scrapper: undefined } };

    const isWebScrapperEnabled = isExperimentEnabled(abExperiments.enable_web_scrapper);
    const isAffordabilityOnboardingActive = user.isShowSegregatedCreditEmi;

    // If affordability Onboarding experiment only then we need to show the credit emi changes
    if (instrument.slug === 'credit' && isAffordabilityOnboardingActive)
      instrument.name = 'Other banks';
    if (CC_EMI_SEPARATE_INSTRUMENT.includes(instrument.slug) && !isAffordabilityOnboardingActive) {
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

    const disabledInstruments = getDisabledInstruments(user);
    const isInstrumentDisabled =
      instrument.status === GREYED || disabledInstruments.includes(instrument.name) || !user.live;
    return (
      <li className={getListClass({ status: instrument.status, path: instrument.path, user })}>
        <ScrapperModal
          isOpen={this.state.isOpen}
          handleModal={this.showScrapperModal}
          createRequestAction={() =>
            this.createRequestAction(instrument, leafInstrument, instrument.path)
          }
          props={this.props}
        />
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
                    onClick={isWebScrapperEnabled ? this.handleScrapperModal : this.handleRequest}
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
        <RequestedStatus instrument={instrument} tat={instrumentsTat} user={user} />

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
      clearIntermediateInstrument,
      clearLeafInstrument,
      fetchRequestedInstruments,
      getIirDiscrepancies,
      setInstrument,
    },
    dispatch,
  );
};

export default withRouter(
  withSplitzService(connect(mapStateToProps, mapDispatchToProps)(LeafListItem)),
);
