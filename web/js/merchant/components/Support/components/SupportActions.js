import ShowWhen from 'merchant/components/ShowWhen';
import PropTypes from 'prop-types';

const noop = () => {};

function SupportActions({
  notifyCount,
  isCallEnabled,
  handleClick,
  shouldDisable,
  scheduleCallbackReason,
  openClickToCall,
  isEligible,
  user,
  supportFlags,
  botIsLoaded,
  timings,
  date,
  isOldFlow,
  isClickToCallSubmitted,
  isChatWithUsDisabled,
  openDashboardGuide,
}) {
  const { show_chat, loaded } = supportFlags;
  const { isClickToCallActive, isFrontendCareActive, isChatbotLive } = user;
  const activationStatus = window?.rzp_user?.activation_status || '';

  const isChatDisabled =
    (!show_chat && notifyCount < 1) || (isChatbotLive && !botIsLoaded) || supportFlags?.isFetching;
  return (
    <ul className="support-list">
      {isOldFlow && (
        <li
          className={`support-item p-all ticket ${!loaded ? 'disabled' : ''}`}
          onClick={!loaded ? noop : handleClick.bind(null, 'tickets')}
        >
          Have a query? <small className="help-block">Check existing query/raise a new one</small>
        </li>
      )}
      <ShowWhen
        myRole="owner admin"
        additionalCondition={() =>
          !(isClickToCallActive || openClickToCall) &&
          !(
            scheduleCallbackReason === 'NOT_FETCHED_YET' ||
            (scheduleCallbackReason === 'NOT_APPLICABLE' && !isEligible)
          )
        }
      >
        <li className="support-item" onClick={handleClick.bind(null, 'schedule-call')}>
          <span>
            Request a call <span className="badge">Recommended</span>
          </span>
          <small className="help-block">{scheduleCallbackReason}</small>
          {!isOldFlow && <i className="i i-chevron-right" />}
        </li>
      </ShowWhen>
      <ShowWhen
        myRole="owner admin"
        additionalCondition={() => isFrontendCareActive && isClickToCallActive && openClickToCall}
      >
        <li
          className={`support-item p-all callback ${isClickToCallSubmitted ? 'disabled' : ''}`}
          onClick={isClickToCallSubmitted ? noop : handleClick.bind(null, 'click-to-call')}
        >
          <span>
            Click to call
            {!isClickToCallSubmitted && <span className="badge">Recommended</span>}
          </span>
          <small className="help-block">
            {isClickToCallSubmitted ? 'You will receive a call shortly' : 'Click to call instantly'}
          </small>

          {!isOldFlow && <i className="i i-chevron-right" />}
        </li>
      </ShowWhen>
      {window.rzp_user ? (
        ['activated', 'under_review', 'instantly_activated', 'needs_clarification'].indexOf(
          activationStatus,
        ) > -1 &&
        (show_chat || isChatWithUsDisabled) ? (
          <li
            className={`support-item p-all chat ${isChatDisabled ? 'disabled' : ''}`}
            onClick={isChatDisabled ? noop : handleClick.bind(null, 'chat')}
          >
            Chat with us
            {timings.length ? (
              <small className="help-content">
                ({date.start} {date.start_zone} - {date.end} {date.end_zone})
              </small>
            ) : null}
            {notifyCount > 0 && <span className="notify-icon m-l">{notifyCount}</span>}
            <small className="help-block">
              {(!show_chat && notifyCount < 1) || isChatWithUsDisabled
                ? 'Currently unavailable'
                : 'For quick questions or help on dashboard'}
            </small>
            {!isOldFlow && <i className="i i-chevron-right" />}
          </li>
        ) : null
      ) : null}

      {isCallEnabled ? (
        <li
          className={`support-item p-all call ${shouldDisable ? 'disabled' : ''}`}
          onClick={shouldDisable ? noop : handleClick.bind(null, 'call')}
        >
          Call Support <small className="help-content">(9am-9pm, working days)</small>
          <small className="help-block">
            {shouldDisable ? 'Currently unavailable' : 'For queries and help on the dashboard'}
          </small>
          {!isOldFlow && <i className="i i-chevron-right" />}
        </li>
      ) : null}

      {isOldFlow && (
        <li className="support-item p-all dashboard_guide" onClick={openDashboardGuide}>
          Dashboard Guide{' '}
          <small className="help-block">Read more about how to use the dashboard</small>
        </li>
      )}
    </ul>
  );
}

SupportActions.defaultProps = {
  isOldFlow: false,
  isEligible: false,
  botIsLoaded: false,
  isCallEnabled: false,
  shouldDisable: false,
  openClickToCall: false,
  isClickToCallSubmitted: false,
  isChatWithUsDisabled: false,
  notifyCount: 0,
  scheduleCallbackReason: '',
  user: {},
  supportFlags: {},
  timings: [],
  handleClick: () => {},
  openDashboardGuide: () => {},
};

SupportActions.propTypes = {
  isCallEnabled: PropTypes.bool,
  isEligible: PropTypes.bool,
  shouldDisable: PropTypes.bool,
  botIsLoaded: PropTypes.bool,
  openClickToCall: PropTypes.bool,
  isOldFlow: PropTypes.bool,
  isClickToCallSubmitted: PropTypes.bool,
  isChatWithUsDisabled: PropTypes.bool,
  handleClick: PropTypes.func,
  openDashboardGuide: PropTypes.func,
  notifyCount: PropTypes.number,
  scheduleCallbackReason: PropTypes.string,
  user: PropTypes.object,
  supportFlags: PropTypes.object,
  timings: PropTypes.array,
  date: PropTypes.object.isRequired,
};

export default SupportActions;
