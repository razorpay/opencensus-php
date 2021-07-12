import React, { useRef, useEffect } from 'react';
import { withRouter } from 'react-router-dom';
import scrollTo from 'common/utils/scrollTo';

function IntoView({ children, location, hashedWith }) {
  const showView = useRef(null);

  useEffect(() => {
    if (location.pathname && location.pathname.includes(hashedWith) && showView.current) {
      const {offsetTop}=showView.current
      if(offsetTop){
        scrollTo({endPos:offsetTop});
      }
      
    }
  },[]);
 
  return (
    <div className="scroll-into-view" ref={showView}>
      {children}
    </div>
  );
}

export default withRouter(IntoView);
