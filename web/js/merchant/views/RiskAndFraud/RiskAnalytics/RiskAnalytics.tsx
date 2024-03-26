import React, { Fragment, useState } from 'react';

import EntityAnalytics from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityAnalytics';

import EntityOverview from './EntityOverview';
import { INITIAL_RATIOS, ENTITY_SECTIONS } from './constants';

const RiskAnalytics = () => {
  const [ratios, setRatio] = useState(INITIAL_RATIOS);

  return (
    <Fragment>
      <EntityOverview setRatio={setRatio} />
      {ENTITY_SECTIONS.map((entity) => {
        return <EntityAnalytics key={entity} ratios={ratios} entity={entity} />;
      })}
    </Fragment>
  );
};

export default RiskAnalytics;
