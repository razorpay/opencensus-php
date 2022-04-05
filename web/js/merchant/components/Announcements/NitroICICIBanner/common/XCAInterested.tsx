import React from 'react';
import { XCAInterestedProps } from '../TypeDeclare/XCATypeDeclare';
import { AsyncBtn } from 'common/new-ui/Button';
import XCATooltip from './XCAtoolTip';
import sanitizer from 'common/utils/xss-sanitizer';

const XCAInterested = ({
  XCAMainText,
  XCASubText,
  save,
}: XCAInterestedProps): React.ReactElement => (
  <div className="xcaMainSection">
    <h3 dangerouslySetInnerHTML={{ __html: sanitizer(XCAMainText) }} />
    <XCATooltip />
    <div className="xcaMainSection--strike" />
    <p dangerouslySetInnerHTML={{ __html: sanitizer(XCASubText) }} />
    <div className="btn-wrapper">
      <AsyncBtn.Primary type="submit" class="btn btn-primary-icici" onClick={save}>
        I am Interested ✨
      </AsyncBtn.Primary>
    </div>
  </div>
);

export default XCAInterested;
