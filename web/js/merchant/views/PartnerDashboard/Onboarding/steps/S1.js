import React from 'react';
import SlideContoller from './SlideController';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import ShowWhen from 'merchant/components/ShowWhen';
import { ONBOARDING_LABELS } from 'merchant/views/PartnerDashboard/constants';
import TnCFooter from 'merchant/views/PartnerDashboard/Onboarding/steps/TnCFooter';

const S1 = ({
  screenName,
  orgDetails,
  sliderProps,
  isLastStep,
  onNext,
  handleOtherCTAClicks,
  onCompleteClick,
}) => {
  return (
    <>
      <div className="partner-onbr-info">
        <div class="title">Welcome to your Partner Dashboard</div>
        <ShowWhen
          additionalCondition={(userData) =>
            !userData.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PartnerCommission)
          }
        >
          <div className="line-box brd-primary">
            <p className="info info-green">Get 0.1% commission on all your referrals.</p>
          </div>
        </ShowWhen>
        <div style={{ marginTop: '21px', padding: '2px' }}>
          <p className="">
            Get started with referring merchants and track your commissions directly from your
            dashboard.
          </p>
          <p className="" style={{ marginTop: '20px' }}>
            First, let’s fill a few more details.
          </p>
          <p className="" style={{ marginTop: '20px' }}>
            <span style={{ color: '#f05050' }}>*</span>Commission details will be shared over mail.
          </p>

          {isLastStep ? (
            <TnCFooter
              orgDetails={orgDetails}
              handleOtherCTAClicks={handleOtherCTAClicks}
              screenName={screenName}
            />
          ) : null}
        </div>
      </div>
      {isLastStep ? (
        <SlideContoller
          sliderProps={sliderProps}
          onNext={onCompleteClick}
          nextBtnLabel={ONBOARDING_LABELS.GET_STARTED}
          nextBtnPendingLabel={ONBOARDING_LABELS.GET_STARTED_PENDING}
        />
      ) : (
        <SlideContoller sliderProps={sliderProps} onNext={onNext} />
      )}
    </>
  );
};

export default S1;
