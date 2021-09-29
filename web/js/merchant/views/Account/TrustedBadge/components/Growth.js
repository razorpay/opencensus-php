import React from 'react';
import PropTypes from 'prop-types';

const Growth = (props) => (
  <div className="row growth-section">
    <div className="col-lg-12">
      <div className="section-head green">
        <div className="title">{props.title}</div>
      </div>
      <div className="rtb-feature-container">
        {props.features.map((feature) => {
          return (
            <div class="rtb-feature col-lg-3" key={feature.title}>
              <img class="rtb-feature-icon" src={feature.icon} />
              <div>
                <div class="rtb-feature-title">{feature.title}</div>
                <p class="rtb-feature-desc">{feature.desc}</p>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  </div>
);

Growth.protoTypes = {
  title: PropTypes.string.isRequired,
  feature: PropTypes.arrayOf(
    PropTypes.shape({
      title: PropTypes.string.isRequired,
      icon: PropTypes.string.isRequired,
      desc: PropTypes.string.isRequired,
    }),
  ),
};

export default Growth;
