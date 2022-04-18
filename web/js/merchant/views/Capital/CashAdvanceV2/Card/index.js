import React, { memo } from 'react';
import './Card.styl';
import PropTypes from 'prop-types';

const WrappedComponent = (props) => {
  const { title, subTitle, imagePath, altText } = props;
  return (
    <div className="card-wrapper">
      <div>
        <img src={imagePath} alt={altText} />
      </div>
      <div className="title">{title}</div>
      <div className="sub-title">{subTitle}</div>
    </div>
  );
};

WrappedComponent.defaultProps = {
  title: '',
  subTitle: '',
  imagePath: '',
  altText: '',
};
WrappedComponent.propTypes = {
  title: PropTypes.string,
  subTitle: PropTypes.string,
  imagePath: PropTypes.string,
  altText: PropTypes.string,
};

const CardComponent = memo(WrappedComponent);
export default CardComponent;
