import sanitizer from 'common/utils/xss-sanitizer';
const FeatureTile = ({ img, featureDesc }) => {
  return (
    <div className="feature-tile">
      <div className="icon-wrapper">
        <img src={img} />
      </div>
      <div className="feature-desc" dangerouslySetInnerHTML={{ __html: sanitizer(featureDesc) }} />
    </div>
  );
};

export const FeatureTiles = ({ cards }) => {
  return (
    <div className="feature-tiles-grid">
      {cards.map((feature, index) => (
        <div key={index}>
          <FeatureTile img={feature?.illustration?.url} featureDesc={feature?.text} />
        </div>
      ))}
    </div>
  );
};
