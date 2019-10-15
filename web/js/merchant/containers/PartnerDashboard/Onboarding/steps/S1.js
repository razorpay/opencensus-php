import React from 'react';
import SlideContoller from './SlideController';

const S1 = props => {
  return (
    <>
      <div className="partner-onbr-info">
        <div class="title">Welcome to your Partner Dashboard</div>
        <div className="line-shadow-box brd-primary">
          <p className="info info-green">
            Receive 0.1% Commission on every transaction{' '}
          </p>
          <p>done by your referred merchant</p>
        </div>
        <div style={{ marginTop: '21px', padding: '2px' }}>
          <p className="">
            Get started with referring merchants and track your commissions and
            all directly from your dashoard.
          </p>
          <p className="" style={{ marginTop: '20px' }}>
            First, let’s fill a few more details.
          </p>
        </div>
      </div>
      <SlideContoller key={4} sliderProps={props.sliderProps} />
    </>
  );
};

export default S1;
