import React from 'react';

const FeaturesList = ({ featuresList }) => {
  const features = featuresList?.map(({ image, description }, index) => (
    <div key={`${image?.alt}-${index}`} className="feature">
      <img src={image?.src} alt={image?.alt} />
      <div className="feature--content">
        <div className="description">{description}</div>
      </div>
    </div>
  ));

  return <div className="features-list">{features}</div>;
};

export default FeaturesList;
