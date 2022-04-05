import React from 'react';
import PropTypes from 'prop-types';
import sanitizer from 'common/utils/xss-sanitizer';

const Details = (props) => {
  return (
    <div class={`row detail-section${props.className ? ` ${props.className}` : ''}`}>
      <div className="col-lg-6">
        <div className={`section-head ${props.headClass ? ` ${props.headClass}` : ''}`}>
          <div className="title">{props.title}</div>
          {props.subtitle && <div className="subtitle">{props.subtitle}</div>}
        </div>
        <div className="section-list">
          {props.details.map((listItem, index) => (
            <div key={index} className="list-item">
              <span dangerouslySetInnerHTML={{ __html: sanitizer(listItem) }} />
            </div>
          ))}
        </div>
        {props.subComponent &&
          props.subComponent.map((component) => props.handleSubComponent(component))}
      </div>
      <div className="col-lg-6 image-center">
        <img src={props.imgSrc} className="intro-image" />
      </div>
    </div>
  );
};

Details.protoTypes = {
  headClass: PropTypes.string,
  title: PropTypes.string.isRequired,
  subtitle: PropTypes.string,
  details: PropTypes.arrayOf(PropTypes.string).isRequired,
  imgSrc: PropTypes.string.isRequired,
  subComponent: PropTypes.arrayOf(PropTypes.string),
  className: PropTypes.string,
};

export default Details;
