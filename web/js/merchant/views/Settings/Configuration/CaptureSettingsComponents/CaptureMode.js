import React, { useState } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import RefundMode from './RefundMode';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { handleTimeoutValues } from './data';
import RefundSpeed from './RefundSpeed';

function CaptureMode(props) {
  const [captureMode, setcaptureMode] = useState(() => {
    if (props.lateAuthConfig.data.items.length === 0) return null;

    return props.lateAuthConfig.data.items[0].config.capture;
  });
  const [timeoutValue, settimeoutValue] = useState(() => {
    if (props.lateAuthConfig.data.items.length === 0) return 7200;

    let tvalue = null;
    const captureOptions = props.lateAuthConfig.data.items[0].config.capture_options;
    if (captureMode === 'automatic') {
      tvalue = captureOptions.automatic_expiry_period;
    } else {
      tvalue = captureOptions.manual_expiry_period;
    }
    return tvalue;
  });

  const onTimeoutValueChange = (e) => {
    const _tValue = parseInt(e.target.value);
    settimeoutValue(_tValue);
  };

  const onClickNext = () => {
    const _tValue = parseInt(timeoutValue);

    if (captureMode === 'manual') {
      props.openModal({
        size: 'medium',
        component: <RefundSpeed captureMode={captureMode} captureModeTimeout={timeoutValue} />,
      });
    } else if (captureMode === 'automatic' && _tValue === 7200) {
      props.openModal({
        size: 'medium',
        component: <RefundSpeed captureMode={captureMode} captureModeTimeout={timeoutValue} />,
      });
    } else if (captureMode === 'automatic' && _tValue !== 7200) {
      props.openModal({
        size: 'medium',
        component: <RefundMode captureMode={captureMode} captureModeTimeout={timeoutValue} />,
      });
    }
  };

  const handleLayout = () => {
    if (captureMode === `automatic`)
      return {
        upper: 'flex-7',
        lower: 'flex-3',
      };
    else
      return {
        upper: 'flex-3',
        lower: 'flex-7',
      };
  };

  // Reset to defaults on close
  const onClose = () => {
    props.closeModal();
  };

  return (
    <div class="capture-mode-container">
      <ModalHeader title="Capture Settings" onCloseClick={onClose} />
      <div class="content">
        <div class={`upper-panel ${captureMode ? 'flex-7' : ''}`}>
          <div class="panel-rows">
            <div class={`${captureMode ? handleLayout().upper : ``}`}>
              <div class="left-col">
                <input
                  type="radio"
                  onClick={(_) => {
                    setcaptureMode(`automatic`);
                  }}
                  checked={captureMode === 'automatic'}
                />
              </div>
              <div class="right-col">
                <strong>Automatic Capture</strong>
                <p class="highlight__subtext">
                  Sit back, relax! Authorised payments will be captured automatically.
                </p>

                {captureMode === `automatic` && (
                  <>
                    <p style={{ fontSize: '12px' }}>Capture all payments authorised within</p>
                    <Input.Select
                      options={handleTimeoutValues(timeoutValue)}
                      defaultValue={timeoutValue}
                      onChange={onTimeoutValueChange}
                    />
                    <p class="highlight__subtext">Min 12 min and maximum 5 days</p>
                  </>
                )}
              </div>
            </div>
            <div class={`highlight-border-top ${captureMode ? handleLayout().lower : ``}`}>
              <div class="left-col">
                <input
                  type="radio"
                  onClick={(_) => {
                    setcaptureMode(`manual`);
                  }}
                  checked={captureMode === 'manual'}
                />
              </div>
              <div class="right-col">
                <strong>Manual Capture</strong>
                <p class="highlight__subtext">
                  Payments have to be captured manually by you via the API or the dashboard
                </p>

                {captureMode === `manual` && (
                  <React.Fragment>
                    <p style={{ fontSize: '12px' }}>Capture payments manually authorised within</p>
                    <Input.Select
                      options={handleTimeoutValues(timeoutValue)}
                      defaultValue={timeoutValue}
                      onChange={onTimeoutValueChange}
                    />
                    <p class="highlight__subtext">Min 12 min and maximum 5 days</p>
                  </React.Fragment>
                )}
              </div>
            </div>
          </div>
        </div>
        <div class={`lower-panel ${captureMode ? `flex-3` : ''}`}>
          <div class="note">
            <p>
              <strong>Note</strong> : Payments not captured within 5 days of creation will be auto
              refunded
            </p>
          </div>
        </div>
      </div>
      <div class="actions">
        <div class="stepper">
          <span class="active" />
          <span />
        </div>
        <div>
          <button
            class="btn btn-primary"
            onClick={onClickNext}
            disabled={captureMode ? false : true}
          >
            Next
          </button>
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
  lateAuthConfig: state.config.lateAuthConfig,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(CaptureMode);
