import { closeModal as closeModalProp } from 'merchant_common/reducers/modals';
import { compose } from 'redux';
import { connect } from 'react-redux';
import React from 'react';
import XCADetailSteps from './common/XCADetailSteps';
import * as xcaConstant from './constant/XCAConstant';
import XCAHeader from './common/XCAHeader';
import XCAInterested from './common/XCAInterested';

const NitroICICIModal = ({ closeModal, save }) => {
  const { XCAStepsToFollow, XCAMainText, XCASubText, XCATooltipText } = xcaConstant;
  return (
    <div className="nitro-self-serve" id="nitro-self-serve">
      <XCAHeader
        XCAHeaderText={xcaConstant.XCAHeaderText}
        imageArr={xcaConstant.XCAheaderImage}
        handleClose={closeModal}
      />
      <div className="headerBottomBorder" />
      <div className="nss-details" id="nss-details">
        <div id="main-section">
          <XCAInterested
            XCAMainText={XCAMainText}
            XCASubText={XCASubText}
            XCATooltipText={XCATooltipText}
            save={save}
          />
          <div className="mainDivider">
            <img src="https://cdn.razorpay.com/static/assets/modal-asset/icici-divider.svg" />
          </div>
          <XCADetailSteps steps={XCAStepsToFollow} />
        </div>
      </div>
      <div className="nitro-footer">
        {xcaConstant.XCAFooterImage.map((item) => (
          <img
            className="nitro-footer__image"
            src={item?.imagePath}
            alt={item?.imageAlt}
            key={item?.imagePath}
          />
        ))}
      </div>
    </div>
  );
};

export default compose(connect(null, { closeModal: closeModalProp }))(NitroICICIModal);
