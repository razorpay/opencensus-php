import React from 'react';
import { XCADetailStepsProps } from '../TypeDeclare/XCATypeDeclare';

const XCADetailSteps = ({ steps }: XCADetailStepsProps): React.ReactElement => (
  <div className="xcaSteps">
    {steps.map((item) => (
      <div className="xcaSteps__detail" key={item?.imagePath}>
        <div>
          <img src={item?.imagePath} alt={item?.imageAlt} />
        </div>
        <p dangerouslySetInnerHTML={{ __html: item?.stepDetails }} />
      </div>
    ))}
  </div>
);

export default XCADetailSteps;
