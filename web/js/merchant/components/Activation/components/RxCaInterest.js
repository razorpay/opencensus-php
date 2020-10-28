import React, { Component } from 'react';
import Button from 'common/new-ui/Button';
import Input, { Description, Error, inputClass } from 'common/new-ui/Input';

const rxBenefits = ['PG Pricing reduced to 1.85%', 'Annual maintenance cost waiver', 'Setup cost waiver', '500 free payouts per month'];

const RxCaInterest = ({ value, onChange, disabled }) => {
    let containerClassName = 'rx-ca';
    if (disabled) {
        containerClassName += ' disable-ca-option';
    }
    return (<div className={containerClassName}>
        <div className='divider' />
        <div className='rx-ca-container'>
            <div className='left-container'>
                <img className='rx-logo' src='/dist/css/assets/razorpayX-logo.png' />
                <div className='text-ca'>
                    Current Account
            </div>
            </div>
            <div className='right-container'>
                <div className='title'>
                    Receive your money in RazorpayX current account & get extra benfits like:
            </div>
                <div>
                    <div className='benefits'>
                        {rxBenefits.map((el, i) => (
                            <div className='info' key={i}>
                                <img className='points-img' src="/dist/css/assets/onboarding/points.svg" />
                                {el}
                            </div>
                        ))}
                    </div>
                    <Input.Check
                        disabled={disabled}
                        className='ca-checkbox'
                        fieldLabel={'Yes, I am interested to open RazorpayX current account'}
                        onChange={onChange}
                        checked={value}
                    />
                    {
                        !!value
                          ?
                          <div className='footer-info'>Our executives will call you for the further process</div>
                          :
                          null
                    }
                </div>
            </div>
        </div>
    </div>);
}

export default RxCaInterest;