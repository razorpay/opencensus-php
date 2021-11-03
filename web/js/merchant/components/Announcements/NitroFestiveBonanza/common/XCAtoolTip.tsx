import React, { useState } from 'react';
import {
  XCAToolTipHome,
  XCAToolTipAlert,
  ToolTipReqHeader,
  ToolTipReqList,
  ToolTipTnCHeader,
  ToolTipTnCList,
} from '../constant/XCAConstant';

const renderToolTip = (header, toolTipList) => (
  <>
    <h6>{header}</h6>
    <ul>
      {toolTipList.map((item) => (
        <li key={item}>{item}</li>
      ))}
    </ul>
  </>
);

const XCATooltip = (): React.ReactElement => {
  const [isToolTip, setToolTip] = useState(false);
  const showToolTip = () => setToolTip(true);
  const handleClose = () => setToolTip(false);

  return (
    <div className="xcaToolTip">
      <img src={XCAToolTipHome?.image} alt={XCAToolTipHome?.imageAlt} />
      <p>In partnership with India’s leading Banks</p>
      <img src={XCAToolTipAlert?.image} alt={XCAToolTipAlert?.imageAlt} onMouseOver={showToolTip} />
      {isToolTip && (
        <div className="xcaToolTip__modal">
          <button type="button" className="xcaToolTip__modal--close" onClick={handleClose}>
            <i className="i i-close" />
          </button>
          {renderToolTip(ToolTipReqHeader, ToolTipReqList)}
          {renderToolTip(ToolTipTnCHeader, ToolTipTnCList)}
        </div>
      )}
    </div>
  );
};

export default XCATooltip;
