import React, { useState } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import RefundMode from './RefundMode';
import CaptureMode from './CaptureMode';
import { fetchLateAuthConfig, createLateAuthConfig } from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import { parseTimeoutValues } from './data';
import { renderTimeoutAsString } from '../PaymentCaptureComponents/util';
import { GraphicalExplanation } from './GraphicalExplanation';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

function RefundSpeed(props) {
  const [refundSpeed, setrefundSpeed] = useState(() => {
    if (props.lateAuthConfig.data.items.length === 0) return 'normal';

    const captureOptions = props.lateAuthConfig.data.items[0].config.capture_options;
    return captureOptions.refund_speed;
  });

  const onPreviousClick = () => {
    if (props.captureMode === 'manual') {
      props.openModal({
        size: 'medium',
        component: <CaptureMode />,
      });
    } else if (props.captureMode === 'automatic' && props.captureModeTimeout === 7200) {
      props.openModal({
        size: 'medium',
        component: <CaptureMode />,
      });
    } else if (props.captureMode === 'automatic' && props.captureModeTimeout !== 7200) {
      props.openModal({
        size: 'medium',
        component: (
          <RefundMode
            captureMode={props.captureMode}
            captureModeTimeout={props.captureModeTimeout}
          />
        ),
      });
    }
  };

  const determineRequestMethod = () => {
    const {
      data: { items },
    } = props.lateAuthConfig;

    if (items.length === 0) return 'POST';
    else {
      let doesHaveDefaultConfig = false;
      items.forEach((item) => {
        if (item.is_default === true) doesHaveDefaultConfig = true;
      });

      if (doesHaveDefaultConfig) return 'PATCH';
      else return 'POST';
    }
  };

  const handleGAEvents = () => {
    let label;

    if (props.captureMode === 'automatic') {
      if (props.captureModeTimeout === 7200) {
        label = `Change | Automatic Capture | Next | Normal Refund | Save`;
      } else {
        // eslint-disable-next-line no-lonely-if
        if (props.timeoutValue === null) {
          label = `Change | Automatic capture | Next | Refund Automatically | Next | Normal Refund | Save`;
        } else {
          label = `Change | Automatic capture | Next | Capture Manually | Next | Refund Speed | Save`;
        }
      }
    } else {
      label = `Change | Manual Capture | Next | Normal Refund | Save`;
    }

    selfServeTrackInitiate({
      selfServeAction: 'Payment Capture Period Updated',
      page: 'Config',
      screen: 'Settings',
    });

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings v2',
      eventAction: 'Save',
      eventLabel: `${label}`,
    });

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Settings',
      eventAction: 'Change - Capture Settings',
      eventLabel: ``,
    });
  };

  const onSaveClick = () => {
    handleGAEvents();

    const method = determineRequestMethod();
    const payload = {
      type: 'late_auth',
      config: { capture: props.captureMode, capture_options: {} },
    };

    if (method === 'POST') {
      payload.name = `late_auth_${props.user.id}`;
      payload.is_default = true;
    }

    // For capture type automatic
    if (props.captureMode === 'automatic') {
      payload.config.capture_options.automatic_expiry_period = props.captureModeTimeout;

      // Sending manual if not skipped
      if (props.timeoutValue) {
        payload.config.capture_options.manual_expiry_period = parseInt(props.timeoutValue, 10);
      }
    } else {
      payload.config.capture_options.manual_expiry_period = parseInt(props.captureModeTimeout, 10);
    }

    payload.config.capture_options.refund_speed = refundSpeed;

    props
      .createLateAuthConfig(payload, method)
      .then(() => {
        props.showNotification({
          type: 'success',
          message: method === 'PATCH' ? 'Preference Updated' : 'Preference Saved',
        });
        props.fetchLateAuthConfig();
      })
      .catch((error) => {
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Payments Capture Settings v2',
          eventAction: 'Error',
          eventLabel: `${error.errors}`,
        });
      });
    props.closeModal();
  };

  const handleLayout = () => {
    if (refundSpeed === `normal`)
      return {
        upper: '',
        lower: '',
      };
    else
      return {
        upper: 'flex-3',
        lower: 'flex-7',
      };
  };

  return (
    <>
      <div class="capture-mode-container">
        <ModalHeader title="Capture Settings" onCloseClick={props.closeModal} />
        <div class="content">
          <div class="info">
            {props.captureMode === 'automatic' && (
              <p class="automatic-info">
                [Automatic Capture Duration :{' '}
                {renderTimeoutAsString(parseTimeoutValues(props.captureModeTimeout))}]
              </p>
            )}
            {props.captureMode === 'manual' && (
              <p class="manual-info">
                [Manual Capture Duration :{' '}
                {renderTimeoutAsString(parseTimeoutValues(props.captureModeTimeout))}]
              </p>
            )}
            {props.timeoutValue && (
              <p class="manual-info">
                [Manual Capture Duration :{' '}
                {renderTimeoutAsString(parseTimeoutValues(props.timeoutValue))}]
              </p>
            )}
            {(props.captureMode === 'automatic' || props.timeoutValue) && (
              <p style={{ marginTop: '10px' }}>
                {props.timeoutValue
                  ? `Payments not authorised/not captured within `
                  : `Payments not authorised by bank within `}
                {renderTimeoutAsString(
                  parseTimeoutValues(
                    props.timeoutValue ? props.timeoutValue : props.captureModeTimeout,
                  ),
                )}{' '}
                will be refunded.
              </p>
            )}
            {props.captureMode === 'manual' && (
              <p style={{ marginTop: '10px' }}>
                Payments not authorised/not captured in{' '}
                {renderTimeoutAsString(parseTimeoutValues(props.captureModeTimeout))} will be
                refunded.
              </p>
            )}
          </div>
          <div class="upper-panel flex-3" style={{ marginTop: '-18px' }}>
            <div
              class={`panel-rows highlight-box ${refundSpeed === 'optimum' ? `m-t-negative` : ''}`}
              style={{ height: '70%' }}
            >
              <div class={`${refundSpeed ? handleLayout().upper : ``}`}>
                <div class="left-col">
                  <input
                    type="radio"
                    onClick={(_) => {
                      setrefundSpeed('normal');
                    }}
                    checked={refundSpeed === `normal`}
                  />
                </div>
                <div class="right-col">
                  <strong>Normal Speed</strong>
                  <p class="highlight__subtext">Refund will be made in 5-7 days.</p>
                </div>
              </div>
              {/* TODO: Commented for now, as there is no support for optimum refund for authorised payments */}

              {/* <div class={`highlight-border-top ${refundSpeed ? handleLayout().lower : ``}`}>
                <div class="left-col">
                  <input
                    type="radio"
                    onClick={(_) => {
                      setrefundSpeed(`optimum`);
                    }}
                    checked={refundSpeed === `optimum`}
                  />
                </div>
                <div class="right-col">
                  <strong>Optimum Speed</strong>
                  <p class="highlight__subtext" style={{ marginBottom: '30px' }}>
                    Refund will be made instantly. A minimal fee would be charged for payments
                    refunded instantly.
                  </p>
                  {refundSpeed === `optimum` && props.refund_pricing.custom_pricing === true && (
                    <>
                      <div class="custom-pricing__contact-support">
                        <div> Minimal fee on each refund </div>
                        <div style={{ color: '#515978' }}>Fees as per agreement with Razorpay</div>
                      </div>
                      <p class="highlight__subtext" style={{ marginBottom: '63px' }}>
                        Currently, Instant refunds are available only on select credit cards, TPV,
                        netbanking and UPI.
                      </p>
                    </>
                  )}
                  {refundSpeed === `optimum` && props.refund_pricing.custom_pricing === false && (
                    <div style={{ marginBottom: '40px' }}>
                      <img
                        src="https://cdn.razorpay.com/static/assets/capture-settings/standard-pricing.png"
                        style={{
                          marginTop: '-20px',
                        }}
                      />
                      <p class="highlight__subtext" style={{ marginBottom: '25px' }}>
                        Currently, Instant refunds are available only on select credit cards, TPV,
                        netbanking and UPI.
                      </p>
                    </div>
                  )}
                </div>
              </div> */}
            </div>
          </div>
          <div class="lower-panel flex-7">
            <div class="note">
              <p>
                <strong>Note</strong> : Instant refunds are available only on captured payments and
                not on authorised payments for now
              </p>
            </div>
          </div>
        </div>
        <div class="actions">
          <div class="stepper">
            <span />
            <span class="active" />
          </div>
          <div>
            <p class="prev-btn" onClick={onPreviousClick}>
              Previous
            </p>
            <button class="btn btn-primary" onClick={onSaveClick} disabled={!refundSpeed}>
              Save
            </button>
          </div>
        </div>
      </div>
      <GraphicalExplanation
        captureMode={props.captureMode}
        timeoutValue={props.captureModeTimeout}
        refundMode={props.timeoutValue ? 'manually' : 'automatically'}
        manuallyCaptureTimeoutValue={props.timeoutValue}
        refundSpeed={refundSpeed}
        animation1={false}
        animation2={true}
      />
    </>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
  lateAuthConfig: state.config.lateAuthConfig,
  refund_pricing: state.config.refund_pricing,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      fetchLateAuthConfig,
      createLateAuthConfig,
      showNotification,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(RefundSpeed);
