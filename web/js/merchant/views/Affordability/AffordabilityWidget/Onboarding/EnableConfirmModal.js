import ModalHeader from 'common/ui/ModalHeader';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import AsyncButton from 'react-async-button';
import { useState, useEffect } from 'react';
import 'merchant/views/Affordability/AffordabilityWidget/affordability-widget-content.styl';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import track from './track';

const EnableConfirmModal = ({ closeModal, pricing, onConfirm, source, ...props }) => {
  const setupSource =
    source === 'banner'
      ? 'disable_banner'
      : `${source === 'others' ? 'native' : source}_set_up_page`;

  const { trialDays } = props;

  useEffect(() => {
    track.enableConfirmRender(setupSource);
  }, []);

  const handleConfirmClick = () => {
    track.enableConfirm(setupSource);
    onConfirm();
  };

  const [tncAccepted, setTncAccepted] = useState(false);
  const showPricing = pricing && pricing.rate;

  return (
    <div className="confirm-enable-modal">
      <ModalHeader title="Enable Affordability Widget" onCloseClick={closeModal} />
      <div className="modal-body">
        <div className="checkbox-row">
          {showPricing ? (
            <>
              <Checkbox
                name="agree"
                title=""
                defaultChecked={tncAccepted}
                onChange={(val) => {
                  setTncAccepted(val);
                }}
              />
              <p>
                By enabling the Affordability Widget, I consent to pay the monthly charges of ₹
                {pricing.rate / 100}
                {pricing.default && pricing.rate < pricing.default ? (
                  <>
                    &nbsp;<span className="discounted-price">₹{pricing.default / 100}</span>
                  </>
                ) : null}{' '}
                per month {trialDays ? ` post ${trialDays}-day free trial` : ''}.
              </p>
            </>
          ) : (
            <p>
              By enabling the Affordability Widget, I consent to enable the widget on my website.
            </p>
          )}
        </div>
        <div className="modal-footer">
          {!showPricing ? (
            <button onClick={closeModal} className="btn btn-border close-btn">
              Cancel
            </button>
          ) : null}

          <AsyncButton
            type="submit"
            disabled={!tncAccepted && showPricing}
            class="btn btn-primary"
            text="Yes, enable"
            onClick={handleConfirmClick}
          />
        </div>
      </div>
    </div>
  );
};

export default compose(
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('EnableConfirmModal'))(EnableConfirmModal),
);
