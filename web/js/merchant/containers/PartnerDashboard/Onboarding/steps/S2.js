import React from 'react';

const s2 = () => {
  return (
    <div className="partner-onbr-info">
      <div className="title">What do you want to do as a Partner?</div>
      <div className="options-group">
        <div className="value active">
          <div style={{ flex: 9 }}>
            <p className="info info-grey">Refer Merchants</p>
            <p>Refer merchants and businesses to Razorpay</p>
          </div>
          <div className="check">
            <i class="i i-check" />
          </div>
        </div>
        <div className="no-top-border value">
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

        <a
          className=""
          style={{
            textDecoration: 'underline',
            color: '#57666E',
            marginTop: '40px',
          }}
        >
          I just want to use Razorpay products
        </a>
      </div>
    </div>
  );
};

export default s2;
