import React from 'react';

import { GenericRecord } from 'merchant/views/MagicCheckout/types';

interface NestedVerticalTabItemProps {
  tabHeading?: string;
  showTabHeading: boolean;
  tabContent: React.ComponentType<{ abExperiments?: GenericRecord }>;
  className?: string;
  abExperiments: GenericRecord;
}

const NestedVerticalTabItem: React.FC<NestedVerticalTabItemProps> = ({
  tabHeading,
  showTabHeading,
  tabContent: Component,
  className,
  abExperiments,
}) => {
  return (
    <div className={`tabs-content bg-white ${className}`}>
      {tabHeading && showTabHeading ? (
        <div className="padding-16 font-20 font-bold tab-heading">{tabHeading}</div>
      ) : null}
      <div className={`tabs-component${!tabHeading ? ' tab-padding' : ''}`}>
        <div className="tabs-component-wrapper">
          <Component abExperiments={abExperiments} />
        </div>
      </div>
    </div>
  );
};

export default NestedVerticalTabItem;
