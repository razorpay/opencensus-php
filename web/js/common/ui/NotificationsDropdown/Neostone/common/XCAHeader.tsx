import React from 'react';
import { XCAHeaderProps } from './../TypeDeclare/XCATypeDeclare';

const XCAHeader = ({
  XCAHeaderText,
  imageArr,
  handleClose,
}: XCAHeaderProps): React.ReactElement => (
  <div className="xcaHeader">
    <div className="xcaHeader__image">
      {imageArr.map((item) => (
        <img
          src={item?.imagePath}
          alt={item?.imageAlt}
          key={item?.imagePath}
          style={item?.imageStyle}
        />
      ))}
    </div>
    <div className="xcaHeader__text">{XCAHeaderText}</div>
    <button type="button" className="xcaHeader__close" onClick={handleClose}>
      <i className="i i-close" />
    </button>
  </div>
);

export default XCAHeader;
