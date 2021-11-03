import React from 'react';
import { XCAInterestedProps } from '../TypeDeclare/XCATypeDeclare';
import { AsyncBtn } from 'common/new-ui/Button';
import XCATooltip from './XCAtoolTip';
import XCATrustedBy from './XCATrustedBy';
import { XCATrustedBrandList } from '../constant/XCAConstant';

const XCAInterested = ({
  XCAMainText,
  XCASubText,
  save,
}: XCAInterestedProps): React.ReactElement => (
  <div className="xcaMainSection">
    <h3 dangerouslySetInnerHTML={{ __html: XCAMainText }} />
    <div className="xcaMainSection--strike" />
    <p dangerouslySetInnerHTML={{ __html: XCASubText }} />
    <div className="btn-wrapper">
      <AsyncBtn.Primary type="submit" class="btn btn-primary" onClick={save}>
        I am Interested ✨
      </AsyncBtn.Primary>
    </div>
    <XCATooltip />
    <XCATrustedBy brandName={XCATrustedBrandList} />
  </div>
);

export default XCAInterested;
