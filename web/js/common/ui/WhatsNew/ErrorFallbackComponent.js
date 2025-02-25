import { compose } from 'redux';
import { connect } from 'react-redux';
import { pushSlider as pushSliderx } from 'merchant_common/reducers/multiSlider';
import React from 'react';

const ErrorFallbackComponent = ({ eventId, pushSlider }) => {
  const handleClick = () => {
    pushSlider({
      component: (
        <div className="js-error-details">
          <banner className="warning">
            <p>
              <b>An error occured, please try again later!</b>
            </p>
            <pre>Error Code: {eventId || 'NA'}</pre>
          </banner>
        </div>
      ),
    });
  };

  return (
    <main className="whats-new-old">
      <div className="whats-new-slide-toggle" onClick={handleClick}>
        Announcements
      </div>
    </main>
  );
};

export default compose(
  connect(null, {
    pushSlider: pushSliderx,
  }),
)(ErrorFallbackComponent);
