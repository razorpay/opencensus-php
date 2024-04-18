import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { Box } from '@razorpay/blade/components';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { togglePaymentsRecapModal as togglePaymentsRecapModalFn } from 'merchant/reducers/paymentsRecap';
import RzpRewindBannerDesktop from 'assets/razorpay-rewind/razorpay-rewind-banner-desktop.png';
import RzpMobileBanner from 'assets/razorpay-rewind/razorpay-rewind-banner-mobile.png';

import { BannerBtn } from './styled';
import { trackPaymentsRecapEvent } from './utils';

const FallbackComponent = () => {
  return null;
};

const PaymentsRecapBanner: React.FC<{
  bannerVariant: 'desktop' | 'mobile';
  togglePaymentsRecapModal: (flag) => void;
  paymentsRecap: any;
}> = ({ bannerVariant, togglePaymentsRecapModal, paymentsRecap }) => {
  const isMobileBanner = bannerVariant === 'mobile';
  const showBanner = paymentsRecap?.showBanner;

  if (!showBanner) return null;

  return (
    <ErrorBoundary
      resetOnProps
      rank={Ranks.P1}
      team={Teams.PG_DASHBOARD}
      FallbackComponent={FallbackComponent}
    >
      <Box
        height="100px"
        borderRadius="medium"
        position="relative"
        marginX="spacing.6"
        paddingTop="20px"
        elevation="highRaised"
      >
        <Box overflow="hidden" height="100%" width="100%" left="0px" position="relative">
          <img
            src={isMobileBanner ? RzpMobileBanner : RzpRewindBannerDesktop}
            width="100%"
            height="100%"
            style={{ objectFit: 'cover' }}
          />
          <BannerBtn
            onClick={() => {
              togglePaymentsRecapModal(true);
              trackPaymentsRecapEvent({
                objectName: 'RZP Rewind Banner',
                actionName: 'Clicked',
              });
            }}
            isMobileBanner={isMobileBanner}
          >
            Check it out now!
          </BannerBtn>
        </Box>
      </Box>
    </ErrorBoundary>
  );
};

const mapStateToProps = (state) => ({
  paymentsRecap: state.paymentsRecap,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      togglePaymentsRecapModal: togglePaymentsRecapModalFn,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PaymentsRecapBanner);
