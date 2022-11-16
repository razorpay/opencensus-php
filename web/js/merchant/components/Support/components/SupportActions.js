import PropTypes from 'prop-types';

const noop = () => {};

function SupportActions({
  notifyCount,
  handleClick,
  openClickToCall,
  supportFlags,
  botIsLoaded,
  chatTiming,
  isClickToCallSubmitted,
  isChatWithUsDisabled,
  openDashboardGuide,
}) {
  const { show_chat, loaded } = supportFlags;
  const activationStatus = window?.rzp_user?.activation_status || '';

  const isChatDisabled =
    (!show_chat && notifyCount < 1) || !botIsLoaded || supportFlags?.isFetching;
  return (
    <ul className="support-list">
      <li
        className={`support-item p-all ticket${!loaded ? ' disabled' : ''}`}
        onClick={!loaded ? noop : handleClick.bind(null, 'tickets')}
      >
        Have a query? <small className="help-block">Check existing query/raise a new one</small>
      </li>

      {openClickToCall && (
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
        </li>
      )}
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
            {chatTiming?.start && chatTiming?.end ? (
              <small className="help-content">
                ({chatTiming.start} {chatTiming.start_zone} - {chatTiming.end} {chatTiming.end_zone}
                )
              </small>
            ) : null}
            {notifyCount > 0 && <span className="notify-icon m-l">{notifyCount}</span>}
            <small className="help-block">
              {(!show_chat && notifyCount < 1) || isChatWithUsDisabled
                ? 'Currently unavailable'
                : 'For quick questions or help on dashboard'}
            </small>
          </li>
        ) : null
      ) : null}

      <li className="support-item p-all dashboard_guide" onClick={openDashboardGuide}>
        Dashboard Guide{' '}
        <small className="help-block">Read more about how to use the dashboard</small>
      </li>
    </ul>
  );
}

SupportActions.defaultProps = {
  botIsLoaded: false,
  openClickToCall: false,
  isClickToCallSubmitted: false,
  isChatWithUsDisabled: false,
  notifyCount: 0,
  supportFlags: {},
  handleClick: () => {},
  openDashboardGuide: () => {},
};

SupportActions.propTypes = {
  botIsLoaded: PropTypes.bool,
  openClickToCall: PropTypes.bool,
  isClickToCallSubmitted: PropTypes.bool,
  isChatWithUsDisabled: PropTypes.bool,
  handleClick: PropTypes.func,
  openDashboardGuide: PropTypes.func,
  notifyCount: PropTypes.number,
  supportFlags: PropTypes.object,
  chatTiming: PropTypes.object.isRequired,
};

export default SupportActions;
