import React from 'react';
import XCADetailSteps from './common/XCADetailSteps';
import XCAInterested from './common/XCAInterested';
import * as xcaConstant from './constant/XCAConstant';

const NitroSelfServeDetails = ({ updateModalView, tracking }): React.ReactElement => {
  const { XCAStepsToFollow, XCAMainText, XCASubText, XCATooltipText } = xcaConstant;
  return (
    <div className="nss-details" id="nss-details">
      <div id="main-section">
        <XCAInterested
          updateModalView={updateModalView}
          XCAMainText={XCAMainText}
          XCASubText={XCASubText}
          XCATooltipText={XCATooltipText}
          tracking={tracking}
        />
        <div className="mainDivider">
          <img
            src={`${window.cdnBaseUrl}/static/assets/neostone/dotted-divider.svg`}
            alt="neo-divider"
          />
        </div>
        <XCADetailSteps steps={XCAStepsToFollow} />
      </div>
    </div>
  );
};

export default NitroSelfServeDetails;
