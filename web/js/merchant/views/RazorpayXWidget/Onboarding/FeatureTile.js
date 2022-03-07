const FeatureTile = ({ icon, iconColor, featureDesc }) => {
  return (
    <div className="feature-tile">
      <div className="icon-wrapper">
        <i class={icon} style={{ '--icon-color': iconColor }} />
      </div>
      <p className="feature-desc">{featureDesc}</p>
    </div>
  );
};

const FeatureTilesList = [
  {
    icon: 'i i-credit-card',
    iconColor: '#6E6ED9',
    featureDesc: (
      <>
        Collateral-free <b>Corporate Card</b>
      </>
    ),
  },
  {
    icon: 'i i-stamp',
    iconColor: '#F36969',
    featureDesc: (
      <>
        Pay <b>taxes</b> in 30 minutes
      </>
    ),
  },
  {
    icon: 'i i-payroll',
    iconColor: '#3BC4FF',
    featureDesc: (
      <>
        Run <b>Payroll</b> in 10 minutes
      </>
    ),
  },
  {
    icon: 'i i-vendor-payments',
    iconColor: '#D4AC0F',
    featureDesc: (
      <>
        1-click
        <b>Vendor Payments</b>
      </>
    ),
  },
  {
    icon: 'i i-thumbs-up-outline',
    iconColor: '#2FB378',
    featureDesc: (
      <>
        Go <b>OTP free</b> with easy approvals
      </>
    ),
  },
  {
    icon: 'i i-book',
    iconColor: '#E069F3',
    featureDesc: (
      <>
        Automated <b>Reconciliation</b>
      </>
    ),
  },
];

export const FeatureTiles = () => {
  return (
    <div className="feature-tiles-grid">
      {FeatureTilesList.map((feature, index) => (
        <div key={index}>
          <FeatureTile
            icon={feature.icon}
            featureDesc={feature.featureDesc}
            iconColor={feature.iconColor}
          />
        </div>
      ))}
    </div>
  );
};
