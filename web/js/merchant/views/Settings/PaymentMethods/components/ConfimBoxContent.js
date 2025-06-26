import { useState } from 'react';
import Input from 'common/new-ui/Input';
import { AsyncButton } from 'react-async-button';

const renderConfirmMessage = (instrumentSlug, numberOfDays) => {
  if (instrumentSlug === 'domestic.sodexo') {
    return <span>Pluxee will be visible on your checkout journey instantly.</span>;
  }

  return (
    <>
      Your business category will undergo additional scrutiny by our banking partners which will
      take approximately {numberOfDays} working days.
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

const ConfirmBoxContext = ({
  isCCEmiEnabled = false,
  instrumentSlug,
  numberOfDays,
  onRequestAbort,
  onRequest,
}) => {
  const isSodexoInstrument = instrumentSlug === 'domestic.sodexo';
  const isCCInstrument = instrumentSlug === 'credit';
  const [isChecked, setIsChecked] = useState(isSodexoInstrument);

  const toggleCheckBox = () => setIsChecked(!isChecked);

  if (isCCInstrument && isCCEmiEnabled)
    return (
      <>
        <Input.Check
          name="instruments"
          defaultValue={false}
          checked={isChecked}
          onChange={toggleCheckBox}
          autoRender
          fieldLabel={
            'I confirm that Credit card EMI for the mentioned banks to be enabled for my customers'
          }
        />
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
            <li>Card & Pluxee under PayU on Optimizer</li>
            <li>Card & Pluxee on PayU's merchant dashboard</li>
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
