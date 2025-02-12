import { parseTimeoutValues, renderTimeoutAsString } from './util';

function Timeouts({ config }) {
  const capture_options = config.capture_options;

  return (
    <div className="timeouts-container">
      {capture_options.automatic_expiry_period && (
        <div className="timeout-item">
          Auto capture timeout{' '}
          <strong>
            {renderTimeoutAsString(parseTimeoutValues(capture_options, 'automatic_expiry_period'))}
          </strong>
        </div>
      )}
      {capture_options.manual_expiry_period && (
        <div className="timeout-item">
          Manual capture timeout{' '}
          <strong>
            {renderTimeoutAsString(parseTimeoutValues(capture_options, 'manual_expiry_period'))}
          </strong>
        </div>
      )}
      <div className="timeout-item">
        {capture_options.refund_speed === 'normal' ? 'Normal' : 'Instant'} refund after{' '}
        <strong>
          {renderTimeoutAsString(
            parseTimeoutValues(
              capture_options,
              capture_options.manual_expiry_period
                ? 'manual_expiry_period'
                : 'automatic_expiry_period',
            ),
          )}
        </strong>
      </div>
    </div>
  );
}

export default Timeouts;
