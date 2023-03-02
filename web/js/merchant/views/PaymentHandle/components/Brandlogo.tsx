import React from 'react';
import { connect } from 'react-redux';
import { getIsTestMode } from 'merchant/views/PaymentHandle/utils';
import rectangleFR from 'assets/payment-handle/rectangle-first-tr.svg';
import rectangleSR from 'assets/payment-handle/rectangle-second-tr.svg';
import rectangleGreyFR from 'assets/payment-handle/rectangle-grey-top.svg';
import rectangleGreySR from 'assets/payment-handle/rectangle-grey-bottom.svg';

interface BrandImagesProps {
  mode: string;
  isGreyScreenVisible: boolean;
}

const BrandImages: React.FC<BrandImagesProps> = ({ mode, isGreyScreenVisible }) => {
  const isTestMode = getIsTestMode(mode);
  const isDisableMode = isTestMode && isGreyScreenVisible;
  return (
    <>
      <img src={isDisableMode ? rectangleGreyFR : rectangleFR} style={{ position: 'absolute' }} />
      <img src={isDisableMode ? rectangleGreySR : rectangleSR} />
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    mode: state.session.mode,
  };
};

export default connect(mapStateToProps, null)(BrandImages);
