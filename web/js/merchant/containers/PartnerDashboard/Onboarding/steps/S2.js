import React from 'react';
import MList from 'component/MList/List';

const s2 = () => {
  return (
    <div className="partner-onbr-info">
      <div className="title">What do you want to do as a Partner?</div>
      <div className="options-group">
        <div className="active">
          <p className="info info-grey">Refer Merchants</p>
          <p>Refer merchants and businesses to Razorpay</p>
        </div>
        <div className="no-top-border">
          <p className="info info-grey">Refer and Manage Merchants</p>
          <p>Refer and manage your customer accounts on Razorpay</p>
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
