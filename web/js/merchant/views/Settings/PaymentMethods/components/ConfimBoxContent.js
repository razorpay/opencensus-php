import { useState } from 'react';
import { STANDARD_PRICING_URL } from 'merchant/views/Settings/PaymentMethods/constants';
import Input from 'common/new-ui/Input';
import { AsyncButton } from 'react-async-button';

const renderConfirmMessage = (instrumentSlug, numberOfDays) => {
  if (instrumentSlug === 'domestic.sodexo') {
    return <span>Sodexo will be visible on your checkout journey instantly.</span>;
  }

  return (
    <>
      This instrument will be enabled for you using &nbsp;
      <span className="toggler-btn">
        <a href={STANDARD_PRICING_URL} target="_blank" rel="noopener noreferrer">
          Standard Pricing <i className="i i-external-link" />
        </a>
      </span>
      . Processing the request roughly takes {numberOfDays} working days.
    </>
  );
};

const ConfirmCheckbox = ({ isSodexoInstrument, isChecked, toggleCheckBox }) => {
  if (isSodexoInstrument) return null;

  return (
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
  );
};

const ConfirmBoxContext = ({ instrumentSlug, numberOfDays, onRequestAbort, onRequest }) => {
  const isSodexoInstrument = instrumentSlug === 'domestic.sodexo';
  const [isChecked, setIsChecked] = useState(isSodexoInstrument);

  const toggleCheckBox = () => setIsChecked(!isChecked);

  return (
    <>
      <div className="payment-method-confirm-message">
        {renderConfirmMessage(instrumentSlug, numberOfDays)}
        <br />
        <br />
        {isSodexoInstrument
          ? 'For a smooth payment experience, please ensure you have enabled the following:'
          : 'Please confirm the following pages are added on your website:'}
        {isSodexoInstrument ? (
          <ul className="confirm-list">
            <li>PayU as a Gateway Provider on Optimizer</li>
            <li>Card & Sodexo under PayU on Optimizer</li>
            <li>Card & Sodexo on PayU's merchant dashboard</li>
          </ul>
        ) : (
          <ul className="confirm-list">
            <li>Terms and Conditions</li>
            <li>Privacy Policy</li>
            <li>Cancellation and Refund</li>
            <li>Shipping and Exchange</li>
            <li>Contact Us</li>
          </ul>
        )}
        <ConfirmCheckbox
          isSodexoInstrument={isSodexoInstrument}
          isChecked={isChecked}
          toggleCheckBox={toggleCheckBox}
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
          text="Request"
          pendingText="Requesting..."
          disabled={!isChecked}
        />
      </div>
    </>
  );
};

export default ConfirmBoxContext;
