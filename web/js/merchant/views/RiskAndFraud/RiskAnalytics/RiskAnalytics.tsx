import React, { Fragment, useState, useEffect, useRef } from 'react';

import EntityAnalytics from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityAnalytics';

import EntityOverview from './EntityOverview';
import { INITIAL_RATIOS, ENTITY_SECTIONS } from './constants';
import { SectionRef } from './types';
import { trackEvent } from '../common/trackEvents';
import './risk-analytics.styl';

const RiskAnalytics = () => {
  const [ratios, setRatio] = useState(INITIAL_RATIOS);
  const sectionRef = useRef<SectionRef>({});

  const assignRef = (entity: string) => (ref: HTMLElement) => (sectionRef.current[entity] = ref);

  useEffect(() => {
    trackEvent({
      objectName: 'Risk Analytics',
      actionName: 'Rendered',
    });
  }, []);

  return (
    <Fragment>
      <EntityOverview setRatio={setRatio} sectionRef={sectionRef} />
      {ENTITY_SECTIONS.map((entity) => {
        return (
          <EntityAnalytics
            key={entity}
            ratios={ratios}
            entity={entity}
            sectionRef={assignRef(entity)}
          />
        );
      })}
    </Fragment>
  );
};

export default RiskAnalytics;
