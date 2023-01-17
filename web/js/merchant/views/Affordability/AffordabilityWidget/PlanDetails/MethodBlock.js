import { useEffect } from 'react';
import RTracking from 'react-tracking';
import { compose } from 'redux';
import track from './track';

const MethodBlock = (props) => {
  const { title, description, redirectLink, imgSource, type } = props;
  useEffect(() => {
    if (type === 'offer') {
      track.offerNudge();
    } else {
      track.paymentOptionNudge();
    }
  }, []);

  const trackNudgeClick = () => {
    track.nudge(type);
  };

  return (
    <div className="block">
      <img src={imgSource} />
      <div className="block-desc-wrapper">
        <div>
          <p className="block-desc-header">{title}</p>
          <p className="block-desc-content">{description}</p>
        </div>

        <a onClick={trackNudgeClick} className="btn-link" href={redirectLink}>
          Enable Now <i className="i i-external-link" />
        </a>
      </div>
    </div>
  );
};
// eslint-disable-next-line babel/new-cap
export default compose(RTracking(() => window.rzpQ.component('MethodBlock'))(MethodBlock));
