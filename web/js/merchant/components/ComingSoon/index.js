import React, { Component } from 'react';
import { classList } from 'common/utils/rzp-utils';
import ProductInfo from './ProductInfo';

// previewURL: in future if we need to show video, load video tag instead of img tag
const ComingSoon = (props) => {
  return (
    <div className={classList('ComingSoon', `ComingSoon--${props.product.replace(' ', '_')}`)}>
      <div className="ComingSoon--Landing">
        <img src={props.previewURL} alt="landing-image" />
      </div>

      <ProductInfo {...props} />
    </div>
  );
};

ComingSoon.propTypes = {
  product: PropTypes.string,
  title: PropTypes.string,
  description: PropTypes.string,
  features: PropTypes.array,
  previewURL: PropTypes.string,
};

export default ComingSoon;
