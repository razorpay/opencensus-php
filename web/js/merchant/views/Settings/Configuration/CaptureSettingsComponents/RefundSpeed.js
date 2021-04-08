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

function RefundSpeed(props) {
  const [refundSpeed, setrefundSpeed] = useState(() => {
    if (props.lateAuthConfig.data.items.length === 0) return null;

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

  const onSaveClick = () => {
    const {
      data: { items },
    } = props.lateAuthConfig;

    let method = '';

    const payload = {
      type: 'late_auth',
      config: { capture: props.captureMode, capture_options: {} },
    };

    if (items.length !== 0) {
      method = 'patch';
    } else {
      method = 'post';
      payload.name = `late_auth_${props.user.id}`;
      payload.is_default = true;
    }

    // For capture type automatic
    if (props.captureMode === 'automatic') {
      payload.config.capture_options.automatic_expiry_period = props.captureModeTimeout;

      // Sending manual if not skipped
      if (props.timeoutValue) {
        payload.config.capture_options.manual_expiry_period = parseInt(props.timeoutValue);
      }
    } else {
      payload.config.capture_options.manual_expiry_period = parseInt(props.captureModeTimeout);
    }

    payload.config.capture_options.refund_speed = refundSpeed;

    props.createLateAuthConfig(payload, method).then(() => {
      props.showNotification({
        type: 'success',
        message: method === 'Patch' ? 'Preference Updated' : 'Preference Saved',
      });
      props.fetchLateAuthConfig();
    });
    props.closeModal();
  };

  const raiseTicket = () => {
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
    }
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
          {props.captureMode === 'manual' && (
            <p style={{ marginTop: '15px' }}>
              Payments not authorised/not captured in{' '}
              {renderTimeoutAsString(parseTimeoutValues(props.captureModeTimeout))} days will be
              refunded.
            </p>
          )}
        </div>
        <div class="upper-panel" style={{ marginTop: props.captureModeTimeout ? '-10px' : '2px' }}>
          <div class="panel-rows highlight-box">
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
                <p class="highlight__subtext" style={{ paddingBottom: '6px' }}>
                  Refund will be made in 4-5 days.
                </p>
              </div>
            </div>
            <div class={`highlight-border-top ${refundSpeed ? handleLayout().lower : ``}`}>
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
                <p class="highlight__subtext">
                  Refund will be made instantly. A minimal fee would be charged for payments
                  refunded instantly.
                </p>
                {refundSpeed === `optimum` && props.refund_pricing.custom_pricing === true && (
                  <div class="custom-pricing__contact-support">
                    <div> Minimal fee on each refund </div>
                    <div style={{ color: '#515978' }}>
                      To know your pricing, please{' '}
                      <a>
                        <strong
                          class="pointer"
                          onClick={() => {
                            window.rzpAnalytics({
                              eventCategory: 'Dashboard - Instant Refund',
                              eventAction: 'Contact Support',
                              eventLabel: `Custom Pricing Modal | Contact Support`,
                            });
                            raiseTicket();
                          }}
                          style={{ color: '#0B70E7' }}
                        >
                          contact support
                        </strong>
                      </a>
                    </div>
                  </div>
                )}
                {refundSpeed === `optimum` && props.refund_pricing.custom_pricing === false && (
                  <div style={{ marginBottom: '40px' }}>
                    <img src="https://cdn.razorpay.com/static/assets/capture-settings/standard-pricing.png" />
                    <p class="highlight__subtext" style={{ marginBottom: '7px' }}>
                      Currently, Instant refunds are available only on select credit cards, TPV,
                      netbanking and UPI.
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
        <div class={`lower-panel`} />
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
          <button
            class="btn btn-primary"
            onClick={onSaveClick}
            disabled={refundSpeed ? false : true}
          >
            Save
          </button>
        </div>
      </div>
    </div>
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
