import React, { useState } from 'react';
import PaypalOnboardingButton from 'merchant/views/Settings/Configuration/PaypalOnboarding';
import { getIcon } from './InstrumentIcons';
import { GREYED } from '../constants';
import { getClassName, getStatusMessage } from '../../Configuration/InternationalPayments';
import { Popover, PopoverBody } from 'common/ui/Popover';
import { connect } from 'react-redux';

const Paypal = ({ instrument, terminals }) => {
  const [isImageLoaded, setIsImageLoaded] = useState(false);
  const [status, setStatus] = useState(terminals.length && terminals[0].terminal.status);
  const disabled = status === GREYED;
  const showStatus = ['created', 'activated', 'permission_missing', 'pending'].includes(status);

  return (
    <li class="paypal-leaf-item">
      <div className={`status-bar ${showStatus ? 'bar-wrap' : 'bar-nowrap'}`}>
        <div className="instrument">
          {instrument.icon && (
            <div class="icon">
              <img
                src={getIcon(instrument.icon)}
                alt={instrument.name}
                width="30px"
                height="30px"
                onLoad={() => setIsImageLoaded(true)}
                style={{
                  display: `${isImageLoaded ? 'initial' : 'none'}`,
                }}
              />
              {!isImageLoaded && (
                <div class="flex">
                  <p className="PlaceholderLoader" />
                </div>
              )}
            </div>
          )}
          <div class="detail">
            <strong>{instrument.name}</strong>
          </div>
          {showStatus ? (
            <a className={`status-pill status-pill-${getClassName(status)}`}>
              <span className="status-text">
                {['created', 'permission_missing'].includes(status) ? 'pending' : status}
              </span>{' '}
              {status === 'activated' && (
                <span>
                  <i className="i i-info-circle" />
                  <Popover theme="dark" align="bottom">
                    <PopoverBody>
                      <div>{getStatusMessage(status)}</div>
                    </PopoverBody>
                  </Popover>
                </span>
              )}
            </a>
          ) : null}
        </div>

        <PaypalOnboardingButton
          showLogo={false}
          disabled={disabled}
          disabledText={instrument.fade_comment}
          isInternationalPayment={true}
          setStatus={(st) => setStatus(st)}
        />
      </div>
      {/* {instrument.description && <p class="desc">{instrument.description}</p>} */}

      {!disabled && (
        <div class="paypal-info mt20">
          <p>
            You can accept Payments in <strong>International Currencies only</strong> using Paypal
          </p>

          <p class="mt10">You CANNOT accept Payments in INR</p>
        </div>
      )}
    </li>
  );
};

const mapStateToProps = (state) => {
  return {
    terminals: state.config.paypal_terminals,
  };
};

export default connect(mapStateToProps, null)(Paypal);
