import React, { useState, useEffect } from 'react';
import { withRouter } from 'react-router-dom';

function TextHighlighter({ children, hashedWith, location }) {
  const [highLight, setHighlight] = useState(false);

  const handleHighlight = () => {
    setHighlight(true);
    setTimeout(() => {
      setHighlight(false);
    }, 5000);
  };

  useEffect(() => {
    const { pathname, hash } = location;

    if (pathname?.includes(hashedWith) || hash?.includes(hashedWith)) {
      handleHighlight();
    }
  }, []);

  return (
    <span className={highLight ? 'highlight-text__inview' : 'highlight-text__remove'}>
      {children}
    </span>
  );
}

export default withRouter(TextHighlighter);
