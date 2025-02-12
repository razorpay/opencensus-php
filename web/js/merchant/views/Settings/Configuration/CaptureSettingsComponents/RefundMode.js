import React, { useState } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import CaptureMode from './CaptureMode';
import RefundSpeed from './RefundSpeed';
import {
  getTimeoutOptions,
  parseTimeoutValues,
  TIMEOUT_VALUES,
  filterTimeoutBasedOnLimit,
  maxTimeoutValue,
} from './data';
import { renderTimeoutAsString } from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/util';
import { GraphicalExplanation } from './GraphicalExplanation';

function RefundMode(props) {
  const [isSelected, setisSelected] = useState(() => {
    if (props.lateAuthConfig.data.items.length === 0) return 'automatically';

    let captureTimeout = null;

    const captureMode = props.lateAuthConfig.data.items[0].config.capture;
    const captureOptions = props.lateAuthConfig.data.items[0].config.capture_options;

    if (captureMode === 'automatic') {
      if (captureOptions.manual_expiry_period) {
        captureTimeout = `manually`;
      } else captureTimeout = `automatically`;
    }

    return captureTimeout;
  });

  const [dropdownValue, setdropdownValue] = useState(() => {
    if (props.lateAuthConfig.data.items.length === 0) return maxTimeoutValue;

    const captureOptions = props.lateAuthConfig.data.items[0].config.capture_options;

    if (isSelected === 'automatically') {
      return maxTimeoutValue;
    } else {
      return props.captureModeTimeout > captureOptions.manual_expiry_period
        ? filterTimeoutBasedOnLimit(TIMEOUT_VALUES, props.captureModeTimeout)[0].name
        : captureOptions.manual_expiry_period;
    }
  });

  const onPreviousClick = () => {
    props.openModal({
      size: 'medium',
      component: <CaptureMode />,
    });
  };

  const onNextClick = () => {
    let label;

    if (isSelected === 'automatically') {
      label = 'Change | Automatic capture | Next | Refund Automatically | Next';
    } else {
      label = 'Change | Automatic capture | Next | Capture Manually | Next';
    }

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings v2',
      eventAction: 'Next',
      eventLabel: `${label}`,
    });

    props.openModal({
      size: 'medium',
      component: (
        <RefundSpeed
          captureMode={props.captureMode}
          captureModeTimeout={props.captureModeTimeout}
          timeoutValue={isSelected === 'automatically' ? null : dropdownValue}
        />
      ),
    });
  };

  const onDropdownValueChange = (e) => {
    const _tValue = parseInt(e.target.value, 10);
    setdropdownValue(_tValue);
  };

  const handleLayout = () => {
    if (typeof isSelected === 'string')
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

  const handleOptions = () => {
    if (props.captureModeTimeout > dropdownValue) {
      return filterTimeoutBasedOnLimit(TIMEOUT_VALUES, props.captureModeTimeout);
    } else {
      return getTimeoutOptions(
        filterTimeoutBasedOnLimit(TIMEOUT_VALUES, props.captureModeTimeout),
        dropdownValue,
      );
    }
  };

  return (
    <>
      <div className="capture-mode-container">
        <ModalHeader title="Capture Settings" onCloseClick={props.closeModal} />
        <div className="content">
          <div className="info">
            <p className="automatic-info">
              [Automatic Capture Duration :{' '}
              {renderTimeoutAsString(parseTimeoutValues(props.captureModeTimeout))}]
            </p>
            <p style={{ marginTop: '13px' }}>
              What do you want to do with payments authorised after{' '}
              {renderTimeoutAsString(parseTimeoutValues(props.captureModeTimeout))}?
            </p>
          </div>
          <div
            className={`upper-panel ${isSelected === 'manually' ? 'flex-7' : ''}`}
            style={{ marginTop: '-6px' }}
          >
            <div
              className={`panel-rows highlight-box ${
                isSelected === 'manually' ? `m-t-negative` : null
              }`}
            >
              <div className={`${isSelected ? handleLayout().upper : ``}`}>
                <div className="left-col">
                  <input
                    type="radio"
                    onClick={(_) => {
                      setisSelected(`automatically`);
                    }}
                    checked={isSelected === 'automatically'}
                  />
                </div>
                <div className="right-col">
                  <strong
                    onClick={(_) => {
                      setisSelected(`automatically`);
                    }}
                  >
                    Refund Automatically
                  </strong>
                  <p className="highlight__subtext" style={{ paddingBottom: '12px' }}>
                    I do not want to accept those payments. Please refund them automatically.
                  </p>
                </div>
              </div>
              <div className={`highlight-border-top ${isSelected ? handleLayout().lower : ``}`}>
                <div className="left-col">
                  <input
                    type="radio"
                    onClick={(_) => {
                      setisSelected(`manually`);
                    }}
                    checked={isSelected === 'manually'}
                  />
                </div>
                <div className="right-col">
                  <strong
                    onClick={(_) => {
                      setisSelected(`manually`);
                    }}
                  >
                    Capture manually via dashboard or API
                  </strong>
                  <p className="highlight__subtext">
                    Payments have to be captured manually by you via the API or the dashboard.
                  </p>

                  {isSelected === 'manually' && (
                    <>
                      <p style={{ fontSize: '12px' }}>
                        Capture payments manually authorised within
                      </p>
                      <Input.Select
                        options={handleOptions()}
                        onChange={onDropdownValueChange}
                        defaultValue={dropdownValue}
                        style={{ marginBottom: '55px' }}
                      />
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
        <div className="lower-panel" />
        <div className="actions">
          <div className="stepper">
            <span />
            <span className="active" />
          </div>
          <div>
            <p className="prev-btn" onClick={onPreviousClick}>
              Previous
            </p>
            <button className="btn btn-primary" onClick={onNextClick} disabled={!isSelected}>
              Next
            </button>
          </div>
        </div>
      </div>
      <GraphicalExplanation
        captureMode={props.captureMode}
        timeoutValue={props.captureModeTimeout}
        refundMode={isSelected}
        manuallyCaptureTimeoutValue={dropdownValue}
        animation1={false}
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

export default connect(mapStateToProps, mapDispatchToProps)(RefundMode);
