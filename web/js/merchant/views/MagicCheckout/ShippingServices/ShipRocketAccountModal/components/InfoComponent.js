import { useCallback } from 'react';
import { STEPS_TEXT } from 'merchant/views/MagicCheckout/ShippingServices/constants';

const Pointer = ({ step }) => {
  return (
    <div className="pointer-container">
      <div className="pointer-circle display-flex flex-center">{step}</div>
    </div>
  );
};

const InfoComponent = ({ step, setStep }) => {
  const {
    instructions: { heading, subheading, points, alternateInstrustion },
    cta: { secondaryStep, secondary, primary },
  } = STEPS_TEXT[step];
  const handleSecondaryClick = () => {
    setStep(secondaryStep);
  };
  const handlePrimaryClick = useCallback(() => {
    setStep(step + 1);
  }, [step]);
  return (
    <div className="link-account-info display-flex">
      <div className="link-account-instruction">
        <div className="row display-flex">
          <div className="col-sm-1 no-padding shipping-services-info-highlights">
            <Pointer step={step + 1} />
          </div>
          <div className="col-sm-11 no-padding">
            <div className="font-bold color-black">{heading}</div>
            <div>
              <div className="link-account-subheading">{subheading}</div>
              <ul className="link-account-list">
                {points.map((item, index) => (
                  <li
                    className={`link-account-list-item ${index === 0 ? ' no-margin-top' : ''}`}
                    key={index}
                  >
                    {item.customPointer ? (
                      <>
                        <span>Click on </span>
                        <a
                          className="font-bold"
                          target="_blank"
                          rel="noreferrer"
                          href="https://app.shiprocket.in/api-user"
                        >
                          CONFIGURE - <i className="i i-edit_board" />
                        </a>
                        <span> under API Section</span>
                      </>
                    ) : (
                      item
                    )}
                  </li>
                ))}
              </ul>
            </div>
            {alternateInstrustion ? (
              <>
                <div className="display-flex align-center">
                  <div className="info-component-divider" />
                  <div className="info-component-divider-text">OR</div>
                  <div className="info-component-divider" />
                </div>
                <div>
                  Alternatively, if you already have an existing API user, please go to the next
                  step and enter the credentials
                </div>
              </>
            ) : null}
          </div>
        </div>
      </div>
      <div className="shipping-services-cta-container display-flex align-center">
        <div className="secondary-cta pointer" onClick={handleSecondaryClick}>
          {secondary}
        </div>
        <div className="primary-cta pointer" onClick={handlePrimaryClick}>
          {primary} <i className="i i-arrow-forward" />
        </div>
      </div>
    </div>
  );
};

export default InfoComponent;
