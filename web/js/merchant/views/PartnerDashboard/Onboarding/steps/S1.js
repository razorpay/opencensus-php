import React from 'react';
import SlideContoller from './SlideController';

const S1 = (props) => {
  return (
    <>
      <div className="partner-onbr-info">
        <div class="title">Welcome to your Partner Dashboard</div>
        <div className="line-box brd-primary">
          <p className="info info-green">
            Get ₹1000 referral bonus and 0.1% commission on all your referrals till 31st of January.
          </p>
        </div>
        <div style={{ marginTop: '21px', padding: '2px' }}>
          <p className="">
            Get started with referring merchants and track your commissions and all directly from
            your dashoard.
          </p>
          <p className="" style={{ marginTop: '20px' }}>
            First, let’s fill a few more details.
          </p>
          <p className="" style={{ marginTop: '20px' }}>
            <span style={{ color: '#f05050' }}>*</span>Commission details will be shared over mail.
          </p>
        </div>
      </div>
      <SlideContoller sliderProps={props.sliderProps} onNext={props.onNext} />
    </>
  );
};

export default S1;
