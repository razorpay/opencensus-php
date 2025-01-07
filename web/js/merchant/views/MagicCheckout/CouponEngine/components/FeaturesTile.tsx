import React from 'react';

interface FeatureTileProps {
  icon: string;
  metric: string;
  featureDesc: React.ReactNode;
  description: string;
}

const FeatureTile: React.FC<FeatureTileProps> = ({ icon, metric, featureDesc, description }) => {
  return (
    <div className="benefit-tile">
      <div className="icon-wrapper">
        <img src={icon} alt="Feature Icon" className="icon-img" />
      </div>
      <div className="benefit-content-wrapper">
        <b className="benefit-metric">{metric}</b>
        <p className="benefit-desc">{featureDesc}</p>
        <p className="benefit-desc-complete">{description}</p>
      </div>
    </div>
  );
};

const FeatureTilesList = [
  {
    icon: require('assets/affordability_widget/aff-cart.svg'),
    metric: '47%',
    featureDesc: <>Increase avg. order value</>,
    description: 'Higher average order value with affordable payment options and offers',
  },
  {
    icon: require('assets/affordability_widget/aff-growth.svg'),
    metric: '57%',
    featureDesc: <>Increase conversion</>,
    description: 'Increase in conversion rates by helping customers make informed choices',
  },
  {
    icon: require('assets/affordability_widget/aff-customers.svg'),
    metric: '38%',
    featureDesc: <>Increase customer delight & loyalty</>,
    description: 'Increase in customer satisfaction with more offers and discounts to choose from',
  },
];

interface FeatureTilesProps {
  expanded?: boolean;
}

const FeatureTiles: React.FC<FeatureTilesProps> = ({ expanded = false }) => {
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

export default FeatureTiles;
