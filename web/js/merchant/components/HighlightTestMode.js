import React, { useState } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import useLocalStorage from 'merchant/utils/useLocalStorage';
import SwitchField from 'common/ui/Forms/SwitchField';

// Match for /paymentpages/new or /paymentpages/*/edit
const isPaymentPagesRegex = /^\/paymentpages\/(new|.+\/edit)$/;

function HighlightTestMode(props) {
  const { user, highlightMode, location, onSwitchMode } = props;

  const [isTestModeFirstTime, setisTestModeFirstTime] = useLocalStorage(
    `isTestModeFirstTime_${user.current}`,
    true,
  );

  const [showTooltip, setshowTooltip] = useState(() => {
    return isTestModeFirstTime;
  });
  const [testModeToggle, setTestModeToggle] = useState(true);

  const handleShowTooltip = () => setshowTooltip((prevState) => !prevState);

  const handleGotItClick = () => {
    setshowTooltip(false);
    setisTestModeFirstTime(false);
  };

  const onToggleTestMode = () => {
    // Switching to the live Mode
    onSwitchMode('live', () => {
      setTestModeToggle(!testModeToggle);
    });
  };

  if (highlightMode === false || ['/activation', '/kyc'].includes(location.pathname)) return null;

  const isPaymentPages = isPaymentPagesRegex.test(location.pathname);

  return (
    <div className={`highlight-test-mode-container${!testModeToggle ? ' hide-test-mode' : ''}`}>
      <div className={`horizontal-line${isPaymentPages ? ' shift-line' : ''}`} />
      <div className="content">
        <div
          className={`trapezoid${isPaymentPages ? ' shift-trapezoid' : ''}`}
          onMouseEnter={handleShowTooltip}
          onMouseLeave={handleShowTooltip}
        >
          YOU&apos;RE IN TEST MODE
          {/* added Toggle switch to switch between Test and Live Mode */}
          <span className="test-mode-switch">
            <SwitchField type="prime round" checked={testModeToggle} onChange={onToggleTestMode} />
          </span>
          <div className={`info${isPaymentPages ? ' shift-info' : ''}`}>
            <i className="i i-info-circle text-fade" />
          </div>
        </div>
        {showTooltip === true && (
          <div className={`info-content${isPaymentPages ? ' shift-info-content' : ''}`}>
            <div>
              Payments in test mode are sample payments, <strong>no real money</strong> is involved
              in these payments.
            </div>
            {isTestModeFirstTime && (
              <div className="clickable">
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
    (state) => ({
      user: state.session.user,
      highlightMode: state.session.highlightMode,
    }),
    null,
  )(HighlightTestMode),
);
