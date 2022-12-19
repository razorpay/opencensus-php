import React from 'react';
import { ProductListItemT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { classList } from 'common/utils/rzp-utils';

const ProductListItem = ({
  icon,
  onClickCTA,
  ctaText,
  title,
  subTitle,
  disabled,
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
          <button type="button" className={classList('product-cta__content')} disabled={disabled}>
            {ctaText}
          </button>
        </div>
      </div>
    </div>
  );
};

export default ProductListItem;
