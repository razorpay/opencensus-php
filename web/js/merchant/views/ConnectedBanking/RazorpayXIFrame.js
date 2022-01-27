import React, { useEffect, useState } from 'react';
import { withRouter } from 'react-router-dom';
import Loader from 'common/ui/Loader';

const RazorpayXIFrame = ({ url, history }) => {
  const [isLoading, setIsLoading] = useState(true);

  const handleBackToHomeCTA = () => {
    history.push('/dashboard');
  };

  const openX = () => {
    window.open(window.bankingServiceUrl, '_blank');
  };

  const iFrameEventListener = (event = {}) => {
    const { origin, data: { type: eventType } = {} } = event;

    if (origin === window.bankingServiceUrl && eventType) {
      switch (eventType) {
        case 'back-to-home':
          handleBackToHomeCTA();
          break;
        case 'account-activated':
          openX();
          handleBackToHomeCTA();
          break;
        default:
          break;
      }
    }
  };

  const loadIFrameListener = () => {
    setIsLoading(false);
    window.addEventListener('message', iFrameEventListener);
  };

  useEffect(() => {
    return () => {
      window.removeEventListener('message', iFrameEventListener);
    };
  }, []);

  return (
    <div className="rx-iframe-container">
      {isLoading ? <Loader /> : null}
      <iframe
        className="razorpayx-iframe"
        onLoad={loadIFrameListener}
        src={url}
        style={{ display: isLoading ? 'none' : 'block' }}
      />
    </div>
  );
};

export default withRouter(RazorpayXIFrame);
