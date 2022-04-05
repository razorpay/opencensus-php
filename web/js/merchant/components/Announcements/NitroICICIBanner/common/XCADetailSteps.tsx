import React from 'react';
import { XCADetailStepsProps } from '../TypeDeclare/XCATypeDeclare';
import sanitizer from 'common/utils/xss-sanitizer';

const XCADetailSteps = ({ steps }: XCADetailStepsProps): React.ReactElement => (
  <div className="xcaSteps">
    {steps.map((item) => (
      <div className="xcaSteps__detail" key={item?.imagePath}>
        <div>
          <img src={item?.imagePath} alt={item?.imageAlt} />
        </div>
        <p dangerouslySetInnerHTML={{ __html: sanitizer(item?.stepDetails) }} />
      </div>
    ))}
  </div>
);

export default XCADetailSteps;
