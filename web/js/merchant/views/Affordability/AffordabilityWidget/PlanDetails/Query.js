import { compose } from 'redux';
import rTracking from 'react-tracking';
import track from './track';

const Query = ({ enabled }) => {
  const trackHelpClick = () => {
    track.requestHelp();
  };

  return (
    <div
      className={`${!enabled ? 'query-wrapper-sm' : ''} query-wrapper block hidden-lg hidden-md`}
    >
      <div>
        <p className="plan-heading">In case of any queries, write to us at</p>
        <a
          target="_blank"
          href="https://razorpay.com/support/#request"
          className="plan-value btn btn-link link"
          rel="noreferrer noopener"
          onClick={trackHelpClick}
        >
          Contact Support
        </a>
      </div>
    </div>
  );
};

export default compose(rTracking(() => window.rzpQ.component('WidgetHelp')))(Query);
