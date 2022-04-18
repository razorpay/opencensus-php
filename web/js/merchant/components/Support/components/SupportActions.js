import ShowWhen from 'merchant/components/ShowWhen';

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
}) {
  const { show_chat } = supportFlags;
  const { isClickToCallActive, isFrontendCareActive, isChatbotLive } = user;
  const activationStatus = window.rzp_user.activation_status;
  return (
    <ul className="support-list">
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
        <li
          className="support-item"
          onClick={() => {
            handleClick('schedule-call');
          }}
        >
          <span>
            Request a call <span className="badge">Recommended</span>
          </span>
          <small className="help-block">{scheduleCallbackReason}</small>
          <i class="i i-chevron-right" />
        </li>
      </ShowWhen>
      <ShowWhen
        myRole="owner admin"
        additionalCondition={() => isFrontendCareActive && isClickToCallActive && openClickToCall}
      >
        <li
          className="support-item p-all callback "
          onClick={() => {
            handleClick('click-to-call');
          }}
        >
          <span>
            Click to call <span className="badge">Recommended</span>
          </span>
          <small className="help-block">Click to call instantly</small>
          <i class="i i-chevron-right" />
        </li>
      </ShowWhen>
      {window.rzp_user ? (
        ['activated', 'under_review', 'instantly_activated', 'needs_clarification'].indexOf(
          activationStatus,
        ) > -1 && show_chat ? (
          <li
            className={`support-item p-all chat ${
              (!show_chat && notifyCount < 1) || (isChatbotLive && !botIsLoaded) ? 'disabled' : ''
            }`}
            onClick={() => {
              handleClick('chat');
            }}
          >
            Chat with us
            {timings.length ? (
              <small className="help-content">
                {date.start} {date.start_zone} - {date.end} {date.end_zone}
              </small>
            ) : null}
            {notifyCount > 0 && <span className="notify-icon m-l">{notifyCount}</span>}
            <small className="help-block">
              {!show_chat && notifyCount < 1
                ? 'Currently unavailable'
                : 'For quick questions or help on dashboard'}
            </small>
            <i class="i i-chevron-right" />
          </li>
        ) : null
      ) : null}
      {isCallEnabled ? (
        <li
          className={`support-item p-all call ${shouldDisable ? 'disabled' : ''}`}
          onClick={() => handleClick('call')}
        >
          Call Support
          <small className="help-content">(9am-9pm, working days)</small>
          <small className="help-block">
            {shouldDisable ? 'Currently unavailable' : 'For queries and help on the dashboard'}
          </small>
          <i class="i i-chevron-right " />
        </li>
      ) : null}
    </ul>
  );
}

export default SupportActions;
