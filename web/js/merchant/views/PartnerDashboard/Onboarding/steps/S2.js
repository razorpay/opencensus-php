import React, { useEffect } from 'react';
import { classList } from 'common/utils/rzp-utils';
import SlideContoller from './SlideController';
import { triggerHotjarHeatmap } from 'common/utils/hotjar';

const s2 = ({ role, onRoleSelect, sliderProps, abort }) => {
  useEffect(() => {
    triggerHotjarHeatmap('pure_platform_control_heatmap');
  }, []);
  return (
    <>
      <div className={'partner-onbr-info'}>
        <div className="title">What do you want to do as a Partner?</div>
        <div className="options-group">
          <div
            class={classList(
              'value',
              role !== 'reseller' && 'no-bottom-border',
              role === 'reseller' && 'active',
            )}
            onClick={() => {
              onRoleSelect('reseller');
            }}
          >
            <div style={{ flex: 9 }}>
              <p className="info info-grey">Refer Businesses</p>
              <p>Refer individuals and businesses to Razorpay</p>
            </div>
            <div className="check">
              <i class="i i-check" />
            </div>
          </div>
          <div
            class={classList(
              'value',
              role !== 'aggregator' && 'no-top-border',
              role === 'aggregator' && 'active',
            )}
            onClick={() => onRoleSelect('aggregator')}
          >
            <div style={{ flex: 9 }}>
              <p className="info info-grey">Refer & Manage Merchants</p>
              <p>Refer and manage the payment stack for your referred accounts</p>
            </div>
            <div className="check">
              <i class="i i-check" />
            </div>
          </div>
        </div>
        <div>
          <p style={{ marginTop: '10px' }}>
            For Enterprise solution
            <a href="https://razorpay.com/support/" target="_blank">
              &nbsp;Contact Support <i className="i i-external-link " />
            </a>
          </p>
          <p style={{ marginTop: '10px' }}>
            <a
              onClick={abort}
              target="_blank"
              style={{
                textDecoration: 'underline',
                color: '#57666E',
              }}
            >
              I just want to use Razorpay products
            </a>
          </p>
        </div>
      </div>
      <SlideContoller sliderProps={sliderProps} disNext={!role} />
    </>
  );
};

export default s2;
