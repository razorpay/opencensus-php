import React from 'react';
import { ProductListItemT } from '../../TypesDeclare/home';

const ProductListItem = ({
  icon,
  onClickCTA,
  ctaText,
  title,
  subTitle,
}: ProductListItemT): JSX.Element => {
  return (
    <div className="product-list-item">
      <img src={icon} alt="banking icon" />
      <div className="product-item" onClick={onClickCTA}>
        <div className="content-group">
          <div className="content-title content-group__title">
            <span>{title}</span>
          </div>
          <span className="content-sub-title">{subTitle}</span>
        </div>
        <div className="product-cta">
          <button type="button" className="product-cta__content">
            {ctaText}
          </button>
        </div>
      </div>
    </div>
  );
};

export default ProductListItem;
