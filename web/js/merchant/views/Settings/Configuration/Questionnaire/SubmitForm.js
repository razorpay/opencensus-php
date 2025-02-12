import React from 'react';
import { Link } from 'react-router-dom';

const SubmitForm = ({ isRevampFlow }) => {
  return (
    <div>
      <div className="main-title">Submit Form</div>
      <div className="Input Input--required Input--checkbox">
        <div className="Input-content">
          <div className="Input-elWrapper">
            <label>
              <input required name="submit" className="Input-el" type="checkbox" />
              <div className="Input-checkbox" />
              <div className="Input-inlineLabel">
                I have read and understood the{' '}
                <a href="https://razorpay.com/terms" target="_blank" rel="noopener noreferrer">
                  Terms &amp; Conditions
                </a>
                ,{' '}
                <a href="https://razorpay.com/agreement" target="_blank" rel="noopener noreferrer">
                  Merchant Agreement
                </a>{' '}
                and the{' '}
                <a href="https://razorpay.com/privacy" target="_blank" rel="noopener noreferrer">
                  Privacy Policy
                </a>
                {'. '}
                By submitting the form, I agree to abide by the rules at all times.
              </div>
            </label>
          </div>
        </div>
      </div>
      {!isRevampFlow && (
        <div className="greyed-out m-t p-t">
          Please review the form before submitting. For any changes after submission, you can{' '}
          <Link to="#ticket">write to support</Link>
        </div>
      )}
    </div>
  );
};

export default SubmitForm;
