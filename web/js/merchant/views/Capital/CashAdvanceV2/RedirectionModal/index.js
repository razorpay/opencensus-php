import React, { useEffect } from 'react';
import './RedirectionModal.styl';

const CASH_ADVANCE_LINK =
  window.APP_ENV == 'production'
    ? `https://x.razorpay.in/cash-advance`
    : ` https://x-echo.np.razorpay.in/cash-advance`;

const CashAdvanceRedirectionModal = (props) => {
  const { onClose } = props;

  useEffect(() => {
    const timeoutId = setTimeout(() => {
      window.open(CASH_ADVANCE_LINK, '_self');
    }, 5000);
    return () => {
      clearTimeout(timeoutId);
    };
  }, []);

  return (
    <div className="cash-x-migration-wrapper">
      <div className="ca-x-migration-container">
        <h1>Applying for Cash Advance</h1>
        <button className="close" onClick={onClose}>
          <i className="i i-close" />
        </button>
        <div className="flex ca-x-migration-inner-wrapper">
          <div className="razorpay-logo-animation ca-x-migration-logo-wrapper">
            <img
              src={`${window.cdnBaseUrl}/static/assets/cash-advance/razorpayLogo.svg`}
              alt="Razorpay-logo"
            />
          </div>
          <div className="ca-x-migration-inner-text">
            You will be re-directed to <b>Razorpay’s onboarding dashboard</b> to continue the
            application process.
          </div>
        </div>
      </div>
    </div>
  );
};

export default CashAdvanceRedirectionModal;
