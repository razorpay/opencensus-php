import ModalHeader from 'common/ui/ModalHeader';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import AsyncButton from 'react-async-button';
import { useState, useEffect } from 'react';
import 'merchant/views/Affordability/AffordabilityWidget/affordability-widget-content.styl';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import track from './track';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { TEST_MODE } from 'merchant/containers/Home/OnboardingCard/data';
import { generatePayload } from './helper';

const EnableConfirmModal = ({ closeModal, pricing, onConfirm, source, ...props }) => {
  const setupSource =
    source === 'banner'
      ? 'disable_banner'
      : `${source === 'others' ? 'native' : source}_set_up_page`;

  const { trialDays, user, showNotification, mode } = props;

  useEffect(() => {
    track.enableConfirmRender(setupSource);
  }, []);

  const handleConfirmClick = () => {
    track.enableConfirm(setupSource);
    onConfirm(generatePayload());
  };

  const [tncAccepted, setTncAccepted] = useState(false);
  const showPricing = pricing && pricing.rate;

  const handleWidgetEnable = () => {
    // If the user is not activated prevent widget enablement
    if (!user.merchant?.activated) {
      showNotification({
        type: 'error',
        message: 'Please activate your Razorpay account to enable the widget.',
      });
      return;
    }

    // If the user is not admin prevent widget enablement
    if (![rolesList.OWNER, rolesList.ADMIN].includes(user.role)) {
      showNotification({
        type: 'error',
        message: "You don't have suffiecient permission to enable the affordability widget",
      });
      return;
    }

    // If the merchant is on test mode prevent widgte enablement
    if (mode === TEST_MODE) {
      showNotification({
        type: 'error',
        message: 'Please enable live mode to enable affordability widget',
      });
      return;
    }

    handleConfirmClick();
  };

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
            className="btn btn-primary"
            text="Yes, enable"
            onClick={handleWidgetEnable}
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
