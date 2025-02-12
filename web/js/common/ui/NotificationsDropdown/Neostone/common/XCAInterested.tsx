import React from 'react';
import { XCAInterestedProps } from '../TypeDeclare/XCATypeDeclare';
import { AsyncBtn } from 'common/new-ui/Button';
import XCATooltip from './XCAtoolTip';
import XCATrustedBy from './XCATrustedBy';
import { XCATrustedBrandList } from '../constant/XCAConstant';
import sanitizer from 'common/utils/xss-sanitizer';

const XCAInterested = ({
  XCAMainText,
  XCASubText,
  updateModalView,
  tracking,
}: XCAInterestedProps): React.ReactElement => {
  const trackCTAClick = () => {
    tracking.trackEvent(window.rzpQ.merchantActions().clicked('nitro_neostone.interested'));
    updateModalView();
  };

  return (
    <div className="xcaMainSection">
      <h3 dangerouslySetInnerHTML={{ __html: sanitizer(XCAMainText) }} />
      <div className="xcaMainSection--strike" />
      <p dangerouslySetInnerHTML={{ __html: sanitizer(XCASubText) }} />
      <div className="btn-wrapper">
        <AsyncBtn.Primary type="submit" className="btn btn-primary" onClick={trackCTAClick}>
          I am Interested ✨
        </AsyncBtn.Primary>
      </div>
      <XCATooltip />
      <XCATrustedBy brandName={XCATrustedBrandList} />
    </div>
  );
};

export default XCAInterested;
