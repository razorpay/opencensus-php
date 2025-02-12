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
  questionnaireStatus,
}) => {
  if (status === 'request_rejected') {
    return (
      <small className="help-content">
        <i className="i i-info-outline" />
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
      <small className="help-content">
        <i className="i i-info-outline" />
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
      <small className="help-content">
        <i className="i i-info-outline" />
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
      <button onClick={onRequestAccessClick} className="btn-link">
        {questionnaireStatus?.new_flow && questionnaireStatus.enablement_progress === 'in_progress'
          ? `Edit draft (${questionnaireStatus.percentage_completion}%)`
          : 'Request Access'}
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
  questionnaireStatus,
}) => {
  return (
    <div className="international__Product">
      <div className="international__ProductInfo">
        <li>
          <strong>{title}</strong>
        </li>
        <div className="international__ProductDescription">{description}</div>
      </div>
      <div className="international__ProductActionAndStatus">
        <ProductAction
          isKycComplete={isKycComplete}
          isWebsiteAdded={isWebsiteAdded}
          product={product}
          status={status}
          showRequestAccessBtn={showRequestAccessBtn}
          onRequestAccessClick={onRequestAccessClick}
          questionnaireStatus={questionnaireStatus}
        />
        {showStatusLabel && <InternationalStatusLabel status={status} />}
      </div>
    </div>
  );
};

export default ProductInfo;
