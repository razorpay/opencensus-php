import React, { useState } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import RefundMode from './RefundMode';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { getTimeoutOptions, TIMEOUT_VALUES, maxTimeoutValue, defaultTimeoutValue } from './data';
import RefundSpeed from './RefundSpeed';
import { GraphicalExplanation } from './GraphicalExplanation';

function CaptureMode(props) {
  const [captureMode, setcaptureMode] = useState(() => {
    if (props.lateAuthConfig.data.items.length === 0) return null;

    return props.lateAuthConfig.data.items[0].config.capture;
  });
  const [timeoutValue, settimeoutValue] = useState(() => {
    if (props.lateAuthConfig.data.items.length === 0) return defaultTimeoutValue;

    let tvalue = null;
    const captureOptions = props.lateAuthConfig.data.items[0].config.capture_options;
    if (captureMode === 'automatic') {
      tvalue = captureOptions.automatic_expiry_period;
    } else {
      tvalue = captureOptions.manual_expiry_period;
    }
    return parseInt(tvalue, 10);
  });

  const onTimeoutValueChange = (e) => {
    const _tValue = parseInt(e.target.value, 10);
    settimeoutValue(_tValue);
  };

  const onClickNext = () => {
    const _tValue = parseInt(timeoutValue, 10);

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings v2',
      eventAction: 'Next',
      eventLabel:
        captureMode === 'automatic'
          ? 'Change | Automatic Capture | Next'
          : 'Change | Manual Capture | Next',
    });

    if (captureMode === 'manual') {
      props.openModal({
        size: 'medium',
        component: <RefundSpeed captureMode={captureMode} captureModeTimeout={timeoutValue} />,
      });
    } else if (captureMode === 'automatic' && _tValue === maxTimeoutValue) {
      props.openModal({
        size: 'medium',
        component: <RefundSpeed captureMode={captureMode} captureModeTimeout={timeoutValue} />,
      });
    } else if (captureMode === 'automatic' && _tValue !== maxTimeoutValue) {
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
    <>
      <div className="capture-mode-container">
        <ModalHeader title="Capture Settings" onCloseClick={onClose} />
        <div className="content">
          <div className={`upper-panel ${captureMode ? 'flex-7' : ''}`}>
            <div className="panel-rows">
              <div className={`${captureMode ? handleLayout().upper : ``}`}>
                <div className="left-col">
                  <input
                    type="radio"
                    onClick={(_) => {
                      setcaptureMode(`automatic`);
                    }}
                    checked={captureMode === 'automatic'}
                  />
                </div>
                <div className="right-col">
                  <strong
                    onClick={(_) => {
                      setcaptureMode(`automatic`);
                    }}
                  >
                    Automatic Capture
                  </strong>
                  <p className="highlight__subtext">
                    Sit back, relax! Authorised payments will be captured automatically.
                  </p>

                  {captureMode === `automatic` && (
                    <>
                      <p style={{ fontSize: '12px' }}>Capture all payments authorised within</p>
                      <Input.Select
                        options={getTimeoutOptions(TIMEOUT_VALUES, timeoutValue)}
                        defaultValue={timeoutValue}
                        onChange={onTimeoutValueChange}
                      />
                      <p className="highlight__subtext">Minimum 12 mins and maximum 3 days</p>
                    </>
                  )}
                </div>
              </div>
              <div className={`highlight-border-top ${captureMode ? handleLayout().lower : ``}`}>
                <div className="left-col">
                  <input
                    type="radio"
                    onClick={(_) => {
                      setcaptureMode(`manual`);
                    }}
                    checked={captureMode === 'manual'}
                  />
                </div>
                <div className="right-col">
                  <strong
                    onClick={(_) => {
                      setcaptureMode(`manual`);
                    }}
                  >
                    Manual Capture
                  </strong>
                  <p className="highlight__subtext">
                    Payments have to be captured manually by you via the API or the dashboard
                  </p>

                  {captureMode === `manual` && (
                    <React.Fragment>
                      <p style={{ fontSize: '12px' }}>
                        Capture payments manually authorised within
                      </p>
                      <Input.Select
                        options={getTimeoutOptions(TIMEOUT_VALUES, timeoutValue)}
                        defaultValue={timeoutValue}
                        onChange={onTimeoutValueChange}
                      />
                      <p className="highlight__subtext">Minimum 12 mins and maximum 3 days</p>
                    </React.Fragment>
                  )}
                </div>
              </div>
            </div>
          </div>
          <div className={`lower-panel ${captureMode ? `flex-3` : ''}`}>
            <div className="note">
              <p>
                <strong>Note</strong> : Payments not captured within 3 days of creation will be auto
                refunded
              </p>
            </div>
          </div>
        </div>
        <div className="actions">
          <div className="stepper">
            <span className="active" />
            <span />
          </div>
          <div>
            <button className="btn btn-primary" onClick={onClickNext} disabled={!captureMode}>
              Next
            </button>
          </div>
        </div>
      </div>
      <GraphicalExplanation
        captureMode={captureMode}
        timeoutValue={timeoutValue}
        animation1={true}
        animation2={true}
      />
    </>
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
