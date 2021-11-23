import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import useLocalStorage from 'merchant/utils/useLocalStorage';

const getAnalyticsObject = (eventAction, eventLabel) => ({
  eventCategory: `Easter egg`,
  eventAction: `${eventAction}`,
  eventLabel: `${eventLabel}`,
});

function FTXPopup({ fnCloseModal, isDesktop, onClaimClick, onKnowMoreClick }) {
  return (
    <div className="ftx-popup">
      {isDesktop ? (
        <i className="i i-close pop-close" onClick={fnCloseModal} />
      ) : (
        <i className="i i-chevron-left pop-close" onClick={fnCloseModal} />
      )}
      <div className="button-container">
        <p className="cta" onClick={onClaimClick}>
          CLAIM YOUR FREE TICKETS
        </p>
      </div>
      <a
        href="https://razorpay.com/ftx/"
        rel="noopener noreferrer"
        target="_blank"
        onClick={onKnowMoreClick}
      >
        Know more
      </a>
    </div>
  );
}

const EasterEgg = (props) => {
  // keep track of component renders/impressions
  // keeping state in sync with localStorage
  const [renders, setRenders] = useLocalStorage(`${props.user.current}-easter-egg-impressions`, 0);

  // keep track of user clicks
  // keeping state in sync with localStorage
  const [clicks, setClicks] = useLocalStorage(`${props.user.current}-easter-egg-clicks`, 0);

  const ref = React.useRef();

  // setup IntersectionObserver to check when easter egg comes into viewport
  // whenever it enters into viewport, updating the impression count
  // also memozing the observer so that new observer isn't created on each render
  const observer = React.useMemo(() => {
    // IntersectionObserver support is not there on all the browsers
    if (window.IntersectionObserver) {
      return new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) {
          setRenders((prevRenderCount) => {
            return prevRenderCount + 1;
          });
        }
      });
    } else {
      return null;
    }
  }, []);

  // observer is unavailable in IE, hence the null checks
  useEffect(() => {
    if (ref.current && observer) {
      observer.observe(ref.current);
    }
    // Remove the observer as soon as the component is unmounted
    return () => {
      if (observer && observer.disconnect) {
        observer.disconnect();
      }
    };
  }, []);

  useEffect(() => {
    if (window.rzpAnalytics) {
      window.rzpAnalytics(getAnalyticsObject('Load easter egg', props.page));
    }
  }, []);

  const updateClicks = () =>
    setClicks((prevClickCount) => {
      return prevClickCount + 1;
    });

  const shouldShowEasterEgg = () => {
    if (renders > 5) return false;

    if (clicks > 2) return false;

    return true;
  };

  const claimTicket = () => {
    window.open('https://razorpay.com/ftx/?coupon_code=FTXEEGG', '_blank');
    if (window.rzpAnalytics) {
      window.rzpAnalytics(getAnalyticsObject('Click claim ticket', props.page));
    }
  };

  const knowMore = () => {
    if (window.rzpAnalytics) {
      window.rzpAnalytics(getAnalyticsObject('Click know more', props.page));
    }
  };

  const clickEasterEgg = () => {
    const screenWidth = window.screen.width;
    updateClicks();
    props.openModal({
      size: screenWidth >= 1024 ? 'regular' : 'small',
      component: (
        <FTXPopup
          fnCloseModal={props.closeModal}
          onClaimClick={claimTicket}
          onKnowMoreClick={knowMore}
          isDesktop={screenWidth >= 1024 && true}
        />
      ),
    });

    if (window.rzpAnalytics) {
      window.rzpAnalytics(getAnalyticsObject('Click easter egg', props.page));
    }
  };

  if (shouldShowEasterEgg() && props.user.isFtxEnabled) {
    return (
      <div ref={ref} className={`ftx-container ${props.extraClass}`} onClick={clickEasterEgg} />
    );
  } else {
    return null;
  }
};

export default connect((state) => ({ user: state.session.user }), { openModal, closeModal })(
  EasterEgg,
);
