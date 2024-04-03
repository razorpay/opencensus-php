import React from 'react';
import { parseTimeoutValues, maxTimeoutValue } from './data';
import { renderTimeoutAsString } from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/util';

const CAPTURE_ASSETS_CDN_URL = 'https://cdn.razorpay.com/static/assets/capture-settings';

export const GraphicalExplanation = ({
  captureMode,
  timeoutValue,
  refundMode,
  manuallyCaptureTimeoutValue,
  refundSpeed,
  animation1,
  animation2,
}) => {
  let timeLabel = renderTimeoutAsString(parseTimeoutValues(timeoutValue));
  let manualTimeoutValue =
    manuallyCaptureTimeoutValue &&
    renderTimeoutAsString(parseTimeoutValues(manuallyCaptureTimeoutValue));

  timeLabel =
    timeLabel &&
    timeLabel
      .replaceAll(' days', 'd')
      .replaceAll(' day', 'd')
      .replaceAll(' minutes', 'min')
      .replaceAll(' minute', 'min')
      .replaceAll(' hours', 'hr')
      .replaceAll(' hour', 'hr');
  manualTimeoutValue =
    manualTimeoutValue &&
    manualTimeoutValue
      .replaceAll(' days', 'd')
      .replaceAll(' day', 'd')
      .replaceAll(' minutes', 'min')
      .replaceAll(' minute', 'min')
      .replaceAll(' hours', 'hr')
      .replaceAll(' hour', 'hr');

  return (
    <div className="graphical-explain-div">
      <div className="graphical-explain-div--header">Your Payment Capture Settings</div>
      <div className="graphical-explain-div--blocks">
        <div className="graphical-explain-div--info graphical-explain-div--info-primary-header">
          <img src={`${CAPTURE_ASSETS_CDN_URL}/customer_init_payment.svg`} />
          Customer Initiates Payment
        </div>
        <div className="graphical-explain-div--indication-1" />
        <div className="graphical-explain-div--indication-2" />
        <div
          className={`graphical-explain-div--steps ${
            animation1 ? 'graphical-explain-div--steps--show-animation' : ''
          }`}
        >
          <div>
            if authorised <span>within</span> {timeLabel}
          </div>
          <div className="graphical-explain-div--info graphical-explain-div--info-secondary-header">
            <img src={`${CAPTURE_ASSETS_CDN_URL}/auto_capture.svg`} />
            {captureMode === 'manual' ? 'Manual Capture' : 'Auto Captured'}
          </div>
        </div>
        <div className="graphical-explain-div--indication-1 graphical-explain-div--indication-1-dashed" />
        <div className="graphical-explain-div--indication-2 graphical-explain-div--indication-2-dashed" />
        {refundMode && refundMode === 'manually' && (
          <>
            <div
              className={`graphical-explain-div--steps ${
                !refundSpeed ? 'graphical-explain-div--steps--show-animation' : ''
              }`}
            >
              <div>
                else if authorised <span>after</span> {timeLabel} <span>till</span>{' '}
                {manualTimeoutValue}
              </div>
              <div className="graphical-explain-div--info graphical-explain-div--info-secondary-header">
                <img src={`${CAPTURE_ASSETS_CDN_URL}/manual_capture.svg`} />
                Manual Capture
              </div>
            </div>
            <div className="graphical-explain-div--indication-1 graphical-explain-div--indication-1-dashed" />
            <div className="graphical-explain-div--indication-2 graphical-explain-div--indication-2-dashed" />
          </>
        )}
        <div
          className={`graphical-explain-div--steps ${
            animation2 ? 'graphical-explain-div--steps--show-animation' : ''
          }`}
        >
          <div>
            else if authorised <span>after</span>{' '}
            {refundMode === 'manually' ? manualTimeoutValue : timeLabel}
          </div>
          <div className="graphical-explain-div--info graphical-explain-div--info-secondary-header">
            {timeoutValue === maxTimeoutValue || captureMode === 'manual' || refundMode ? (
              <>
                <img src={`${CAPTURE_ASSETS_CDN_URL}/auto_refund.svg`} />
                Auto Refund
                {refundSpeed !== undefined ? (
                  refundSpeed === 'optimum' ? (
                    <span className="refund-speed"> (Instant)</span>
                  ) : (
                    <span className="refund-speed"> (Normal)</span>
                  )
                ) : null}
              </>
            ) : (
              <span>
                Click <span>next</span> to setup
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
