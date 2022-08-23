import { useState } from 'react';
import { STANDARD_PRICING_URL } from '../constants';
import Input from 'common/new-ui/Input';
import { AsyncButton } from 'react-async-button';

const ConfirmBoxContext = ({ numberOfDays, onRequestAbort, onRequest }) => {
  const [isChecked, setIsChecked] = useState(false);

  const toggleCheckBox = () => {
    setIsChecked(!isChecked);
  };

  return (
    <>
      <div className="payment-method-confirm-message">
        This instrument will be enabled for you using &nbsp;
        <span className="toggler-btn">
          <a href={STANDARD_PRICING_URL} target="_blank" rel="noopener noreferrer">
            Standard Pricing <i className="i i-external-link" />
          </a>
        </span>
        . Processing the request roughly takes {numberOfDays} working days.
        <br />
        <br />
        Please confirm the following pages are added on your website:
        <ul className="confirm-list">
          <li>Terms and Conditions</li>
          <li>Privacy Policy</li>
          <li>Cancellation and Refund</li>
          <li>Shipping and Exchange</li>
          <li>Contact Us</li>
        </ul>
        <Input.Check
          name="instruments"
          defaultValue={false}
          checked={isChecked}
          onChange={toggleCheckBox}
          autoRender
          fieldLabel={
            "I've added these pages and understand that my request will be rejected without them"
          }
        />
        <br />
      </div>
      <div className="Modal__actions">
        <button type="button" className="btn btn-outline" onClick={onRequestAbort}>
          Cancel
        </button>
        <AsyncButton
          type="button"
          className="btn btn-primary"
          onClick={onRequest}
          text={'Request'}
          pendingText={'Requesting...'}
          disabled={!isChecked}
        />
      </div>
    </>
  );
};

export default ConfirmBoxContext;
