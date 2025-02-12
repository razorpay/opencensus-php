import React from 'react';
import Banner from 'common/ui/Banner';

const onHoldBanner = ({ ctaOnClick, user }) => {
  let content = <>Your settlements are not being processed. They have been put on hold.</>;

  if (user.instantActivation.isWhitelistFlow || user.isUnregisteredBusiness) {
    if (
      user.activation_status === 'under_review' ||
      user.activation_status === 'kyc_qualified_unactivated'
    ) {
      content = (
        <>
          Your Settlements are not being processed currently because your KYC is pending review. It
          generally takes 1-2 working days <strong>from the first transaction</strong> for the
          review process to be complete.
        </>
      );
    } else if (user.isActivated && !user.isSubmitted) {
      content = (
        <>
          Your Settlements are not being processed currently. Once your KYC is submitted and
          approved, settlements will be processed.
        </>
      );
    }
  }

  return (
    <div className="TestModeBanner">
      <Banner>
        {content}
        &nbsp;
        <span onClick={ctaOnClick} className="btn-link">
          View Details
        </span>
      </Banner>
    </div>
  );
};

export default onHoldBanner;
