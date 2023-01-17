import 'merchant/views/Affordability/components/featuretiles/feature-tiles.styl';
import { useEffect } from 'react';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import { FeatureTilesList } from './data';
import track from './track';
import trackDetails from 'merchant/views/Affordability/AffordabilityWidget/PlanDetails/track';

const FeatureTile = ({ icon, metric, featureDesc, description }) => {
  return (
    <div className="benefit-tile">
      <div className="icon-wrapper">
        <img src={icon} className="icon-img" />
      </div>
      <div className="benefit-content-wrapper">
        <b className="benefit-metric">{metric}</b>
        <p className="benefit-desc">{featureDesc}</p>
        <p className="benefit-desc-complete">{description}</p>
      </div>
    </div>
  );
};

const FeatureTiles = ({ expanded = false }) => {
  useEffect(() => {
    if (!expanded) {
      track.productDetailsRender();
    } else {
      trackDetails.widgetBenefitsRender();
    }
  }, []);

  return (
    <div className={`benefit-tiles-grid ${expanded ? 'tiles-expanded' : ''}`}>
      {FeatureTilesList.map((feature, index) => (
        <FeatureTile
          icon={feature.icon}
          featureDesc={feature.featureDesc}
          metric={feature.metric}
          description={feature.description}
          key={index}
        />
      ))}
    </div>
  );
};

// eslint-disable-next-line babel/new-cap
export default compose(RTracking(() => window.rzpQ.component('FeatureTiles'))(FeatureTiles));
