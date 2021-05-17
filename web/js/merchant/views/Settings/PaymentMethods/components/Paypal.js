import React, { useState } from 'react';
import PaypalOnboardingButton from 'merchant/views/Settings/Configuration/PaypalOnboarding';
import { getIcon } from './InstrumentIcons';
import { GREYED } from '../constants';

const Paypal = ({ instrument }) => {
  const [isImageLoaded, setIsImageLoaded] = useState(false);
  let disabled = instrument.status === GREYED;

  return (
    <li class="paypal-leaf-item">
      <div>
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
        <PaypalOnboardingButton
          showLogo={false}
          disabled={disabled}
          disabledText={instrument.fade_comment}
        />
      </div>
      {instrument.description && <p class="desc">{instrument.description}</p>}

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

export default Paypal;
