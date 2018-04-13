import { Link } from 'react-router-dom';

export default () => {
  return (
    <a
      className="Onboarding__Step"
      href="//docs.razorpay.com/v1/docs"
      target="_blank"
    >
      <div className="media">
        <div className="media-body">
          <b>Integrate in Test Mode</b>
          <div className="step-desc">Go through Integration Docs</div>
        </div>
        <div className="media-arrow">
          <i className="i i-chevron-right" />
        </div>
      </div>
    </a>
  );
};
