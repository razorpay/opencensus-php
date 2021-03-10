import React from 'react';
import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';
import Popover, { PopoverBody } from 'common/ui/Popover';

const ProductAction = ({
  status,
  product,
  isWebsiteAdded,
  isKycComplete,
  showRequestAccessBtn,
  onRequestAccessClick,
}) => {
  if (status === 'request_rejected') {
    return (
      <small class="help-content">
        <i class="i i-info-outline" />
        <Popover align="top" theme="dark">
          <PopoverBody>
            <div>
              We can currently not support international payments for these products. Please reach
              out to support for any queries
            </div>
          </PopoverBody>
        </Popover>
      </small>
    );
  }

  if (product === 'otherProducts' && !isKycComplete) {
    return (
      <small class="help-content">
        <i class="i i-info-outline" />
        <Popover align="top" theme="dark">
          <PopoverBody>
            <div>You need to complete your KYC to request access.</div>
          </PopoverBody>
        </Popover>
      </small>
    );
  }

  if (product === 'otherProducts' && !isWebsiteAdded) {
    return (
      <small class="help-content">
        <i class="i i-info-outline" />
        <Popover align="top" theme="dark">
          <PopoverBody>
            <div>
              You need to add your website to request access for international payments on other
              products.
            </div>
          </PopoverBody>
        </Popover>
      </small>
    );
  }

  if (showRequestAccessBtn) {
    return (
      <button onClick={onRequestAccessClick} class="btn-link">
        Request Access
      </button>
    );
  }

  return null;
};

const ProductInfo = ({
  title,
  description,
  status,
  showRequestAccessBtn,
  onRequestAccessClick,
  isWebsiteAdded,
  isKycComplete,
  product,
  showStatusLabel,
}) => {
  return (
    <div class="international__Product">
      <div class="international__ProductInfo">
        <li>
          <strong>{title}</strong>
        </li>
        <div class="international__ProductDescription">{description}</div>
      </div>
      <div class="international__ProductActionAndStatus">
        <ProductAction
          isKycComplete={isKycComplete}
          isWebsiteAdded={isWebsiteAdded}
          product={product}
          status={status}
          showRequestAccessBtn={showRequestAccessBtn}
          onRequestAccessClick={onRequestAccessClick}
        />
        {showStatusLabel && <InternationalStatusLabel status={status} />}
      </div>
    </div>
  );
};

export default ProductInfo;
