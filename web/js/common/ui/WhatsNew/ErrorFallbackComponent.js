import { compose } from 'redux';
import { connect } from 'react-redux';
import { pushSlider as pushSliderx } from 'merchant_common/reducers/multiSlider';

const ErrorFallbackComponent = ({ eventId, pushSlider, error, info }) => {
  const handleClick = () => {
    pushSlider({
      component: (
        <div className="js-error-details">
          <banner className="warning">
            <p>
              <b>An Error Occured</b>
            </p>
            <pre>{eventId}</pre>
            <pre>{error?.toString()}</pre>
            <pre>{info?.componentStack?.replace(/^\n/gm, '')}</pre>
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
