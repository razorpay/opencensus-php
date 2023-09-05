import React from 'react';

const TabContent = (props) => {
  const { htmlId, controlledBy, children } = props;

  return (
    <div id={htmlId} role="tabpanel" aria-labelledby={controlledBy} className="vtab__tabpanel">
      {children}
    </div>
  );
};

export default TabContent;
