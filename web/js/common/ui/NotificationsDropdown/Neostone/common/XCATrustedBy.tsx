import React from 'react';
import { XCATrustedProps, ArrayOfImage } from '../TypeDeclare/XCATypeDeclare';

const XCABrandName = ({ imagePath, imageAlt }: ArrayOfImage): React.ReactElement => (
  <div className="xcaTrust__carousel--brand">
    <img src={imagePath} alt={imageAlt} />
  </div>
);

const XCATrustedBy = ({ brandName }: XCATrustedProps): React.ReactElement => (
  <div className="xcaTrust">
    <h4>Trusted by</h4>
    <div className="xcaTrust__carousel">
      <div className="strip">
        {brandName.map((item) => (
          <XCABrandName key={item.imagePath} imagePath={item.imagePath} imageAlt={item.imageAlt} />
        ))}
        {brandName.map((item) => (
          <XCABrandName key={item.imagePath} imagePath={item.imagePath} imageAlt={item.imageAlt} />
        ))}
      </div>
      <div className="cover">
        <div className="left" />
        <div className="right" />
      </div>
    </div>
  </div>
);

export default XCATrustedBy;
