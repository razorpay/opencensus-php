import React, { useRef, useEffect } from 'react';
import { withRouter } from 'react-router-dom';
import scrollTo from 'common/utils/scrollTo';

/**
 * Component to scroll the `children` component into view
 *
 * @param {React.Component} children - Component to scroll into view
 * @param {(string|string[])} hashedWith - scroll if `hashedWith` route(s) is present in the url
 * @return {React.Component} Component
 * @example <IntoView hashedWith={"route"}><ChildComponent /></IntoView>
 * @example <IntoView hashedWith={["route1", "route2"]}><ChildComponent /></IntoView>
 */
function IntoView({ children, location, hashedWith }) {
  const showView = useRef(null);

  useEffect(() => {
    if (!location.pathname) return;

    const urlSegments = location.pathname.split('/');

    const hashPresent = Array.isArray(hashedWith)
      ? hashedWith.some((hash) => urlSegments.includes(hash))
      : urlSegments.includes(hashedWith);

    if (hashPresent && showView.current) {
      // add delay to wait for whole dom to load then scroll to the target element
      setTimeout(() => {
        const { offsetTop } = showView.current;
        if (offsetTop) {
          scrollTo({ endPos: offsetTop });
        }
      }, 500);
    }
  }, []);

  return (
    <div className="scroll-into-view" ref={showView}>
      {children}
    </div>
  );
}

export default withRouter(IntoView);
