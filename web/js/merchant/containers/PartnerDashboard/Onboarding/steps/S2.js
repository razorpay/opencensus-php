import React from 'react';
import { classList } from 'common/util';
import SlideContoller from './SlideController';

const s2 = ({ role, onRoleSelect, sliderProps }) => {
  return (
    <>
      <div className={'partner-onbr-info'}>
        <div className="title">What do you want to do as a Partner?</div>
        <div className="options-group">
          <div
            class={classList('value', role === 'reseller' && 'active')}
            onClick={() => {
              onRoleSelect('reseller');
            }}
          >
            <div style={{ flex: 9 }}>
              <p className="info info-grey">Refer Merchants</p>
              <p>Refer merchants and businesses to Razorpay</p>
            </div>
            <div className="check">
              <i class="i i-check" />
            </div>
          </div>
          <div
            class={classList(
              'top-no-border',
              'value',
              role === 'aggregator' && 'active'
            )}
            onClick={() => onRoleSelect('aggregator')}
          >
            <div style={{ flex: 9 }}>
              <p className="info info-grey">Refer and Manage Merchants</p>
              <p>Refer and manage your customer accounts on Razorpay</p>
            </div>
            <div className="check">
              <i class="i i-check" />
            </div>
          </div>
        </div>
        <div class="partner--role-notes">
          <p>
            For Enterprise solution Contact &nbsp;
            <a href="mailto:partnership@razorpay.com" target="_top">
              Support
            </a>
          </p>
          <p style={{ marginTop: '10px' }}>
            <a
              target="_blank"
              style={{
                textDecoration: 'underline',
                color: '#57666E',
              }}
            >
              I just want to use Razorpay products{' '}
            </a>
            <i className="i i-external-link " />
          </p>
        </div>
      </div>
      <SlideContoller sliderProps={sliderProps} disNext={!Boolean(role)} />
    </>
  );
};

export default s2;
