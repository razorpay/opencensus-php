import React, { useState } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import CaptureMode from './CaptureMode';
import RefundSpeed from './RefundSpeed';
import { handleTimeoutValues, parseTimeoutValues } from './data';
import { renderTimeoutAsString } from '../PaymentCaptureComponents/util';

function RefundMode(props) {
  const [isSelected, setisSelected] = useState(() => {
    if (props.lateAuthConfig.data.items.length === 0) return null;

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
    if (props.lateAuthConfig.data.items.length === 0) return 7200;

    const captureOptions = props.lateAuthConfig.data.items[0].config.capture_options;

    if (isSelected === 'automatically') {
      return 7200;
    } else {
      return captureOptions.manual_expiry_period;
    }
  });

  const onPreviousClick = () => {
    props.openModal({
      size: 'medium',
      component: <CaptureMode />,
    });
  };

  const onNextClick = () => {
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
    const _tValue = parseInt(e.target.value);
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

  return (
    <div class="capture-mode-container">
      <ModalHeader title="Capture Settings" onCloseClick={props.closeModal} />
      <div class="content">
        <div class="info">
          <p class="automatic-info">
            [Automatic Capture Duration :{' '}
            {renderTimeoutAsString(parseTimeoutValues(props.captureModeTimeout))}]
          </p>
          <p style={{ marginTop: '15px' }}>
            What do you want to do with payments authorised after{' '}
            {renderTimeoutAsString(parseTimeoutValues(props.captureModeTimeout))}?
          </p>
        </div>
        <div
          class={`upper-panel ${isSelected === 'manually' ? 'flex-7' : ''}`}
          style={{ marginTop: '-10px' }}
        >
          <div class="panel-rows">
            <div class={`${isSelected ? handleLayout().upper : ``}`}>
              <div class="left-col">
                <input
                  type="radio"
                  onClick={(_) => {
                    setisSelected(`automatically`);
                  }}
                  checked={isSelected === 'automatically'}
                />
              </div>
              <div class="right-col">
                <strong>Refund Automatically</strong>
                <p class="highlight__subtext" style={{ paddingBottom: '6px' }}>
                  I do not want to accept those payments. Please refund them automatically.
                </p>
              </div>
            </div>
            <div class={`highlight-border-top ${isSelected ? handleLayout().lower : ``}`}>
              <div class="left-col">
                <input
                  type="radio"
                  onClick={(_) => {
                    setisSelected(`manually`);
                  }}
                  checked={isSelected === 'manually'}
                />
              </div>
              <div class="right-col">
                <strong>Capture manually them via dashboard or API</strong>
                <p class="highlight__subtext">
                  Payments have to be captured manually by you via the API or the dashboard.
                </p>

                {isSelected === 'manually' && (
                  <>
                    <p style={{ fontSize: '12px' }}>Capture manually them via dashboard or API</p>
                    <Input.Select
                      options={handleTimeoutValues(dropdownValue, true)}
                      onChange={onDropdownValueChange}
                      defaultValue={dropdownValue}
                    />
                    <p class="highlight__subtext" style={{ marginBottom: '55px' }}>
                      Min 60 min and maximum 5 days
                    </p>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
        <div class={`lower-panel ${isSelected ? `flex-3` : ''}`} />
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
            onClick={onNextClick}
            disabled={isSelected ? false : true}
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

export default connect(mapStateToProps, mapDispatchToProps)(RefundMode);
