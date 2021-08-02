import React, { useState } from 'react';
import useLocalStorage from 'merchant/utils/useLocalStorage';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

function HighlightTestMode(props) {
  const { user, highlightMode, location } = props;

  const [isTestModeFirstTime, setisTestModeFirstTime] = useLocalStorage(
    `isTestModeFirstTime_${user.current}`,
    true,
  );

  const [showTooltip, setshowTooltip] = useState(() => {
    return isTestModeFirstTime;
  });

  const handleShowTooltip = () => setshowTooltip((prevState) => !prevState);

  const handleGotItClick = () => {
    setshowTooltip(false);
    setisTestModeFirstTime(false);
  };

  if (highlightMode === false || location.pathname === '/activation') return null;

  const isPaymentPages = location.pathname === '/paymentpages/new';

  return (
    <div class="highlight-test-mode-container">
      <div class={`horizontal-line ${isPaymentPages ? 'shift-line' : null}`} />
      <div class="content">
        <div
          class={`trapezoid ${isPaymentPages ? 'shift-trapezoid' : null}`}
          onMouseEnter={handleShowTooltip}
          onMouseLeave={handleShowTooltip}
        >
          YOU&apos;RE IN TEST MODE
        </div>
        <div
          class={`info ${isPaymentPages ? 'shift-info' : null}`}
          onMouseEnter={handleShowTooltip}
          onMouseLeave={handleShowTooltip}
        >
          <i className="i i-info-circle text-fade" />
        </div>
        {showTooltip === true && (
          <div class={`info-content ${isPaymentPages ? 'shift-info-content' : null}`}>
            <div>
              Payments in test mode are sample payments, <strong>no real money</strong> is involved
              in these payments.
            </div>
            {isTestModeFirstTime && (
              <div class="clickable">
                <strong onClick={handleGotItClick}>GOT IT</strong>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

export default withRouter(
  connect(
    (state) => ({ user: state.session.user, highlightMode: state.session.highlightMode }),
    null,
  )(HighlightTestMode),
);
