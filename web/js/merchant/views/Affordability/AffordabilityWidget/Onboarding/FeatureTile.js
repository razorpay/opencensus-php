import affCart from 'assets/affordability_widget/aff-cart.svg';
import affCustomers from 'assets/affordability_widget/aff-customers.svg';
import affGrowth from 'assets/affordability_widget/aff-growth.svg';
const FeatureTile = ({ icon, metric, featureDesc }) => {
  return (
    <div className="benefit-tile">
      <div className="icon-wrapper">
        <img src={icon} className="icon-img" />
      </div>
      <b className="benefit-metric">{metric}</b>
      <p className="benefit-desc">{featureDesc}</p>
    </div>
  );
};

const FeatureTilesList = [
  {
    icon: affCart,
    metric: '47%',
    featureDesc: <>Increase in order value</>,
  },
  {
    icon: affGrowth,
    metric: '57%',
    featureDesc: <>Growth in conversion rates</>,
  },
  {
    icon: affCustomers,
    metric: '38%',
    featureDesc: <>Higher customer satisfaction</>,
  },
];

export const FeatureTiles = () => {
  return (
    <div className="benefit-tiles-grid">
      {FeatureTilesList.map((feature, index) => (
        <div key={index}>
          <FeatureTile
            icon={feature.icon}
            featureDesc={feature.featureDesc}
            metric={feature.metric}
          />
        </div>
      ))}
    </div>
  );
};
