import { useState, useEffect, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { createShippingProviders } from 'merchant/reducers/magicCheckout/shipping_services/actions';
import { showNotification } from 'merchant_common/reducers/notifications';
import Input from 'common/new-ui/Input';
import {
  EMAIL_REGEX,
  SHIPPING_PARTNERS,
} from 'merchant/views/MagicCheckout/ShippingServices/constants';

const ShipRocketForm = ({ step, setStep, createProviders, displayNotification, closeModal }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isCtaEnabled, setIsCtaEnabled] = useState(false);

  const handleSecondaryClick = useCallback(() => {
    setStep(step - 1);
  }, [step, setStep]);

  const checkCtaEnabled = (newEmail, enteredPassword) => {
    const regex = new RegExp(EMAIL_REGEX);
    setIsCtaEnabled(enteredPassword && regex.test(newEmail));
  };

  const handleInputChange = useCallback((e) => {
    const { value, type } = e.target;
    if (type === 'email') {
      setEmail(value);
    } else {
      setPassword(value);
    }
  }, []);

  const handleShiprocketConnect = useCallback(() => {
    setIsCtaEnabled(false);
    createProviders({
      providerType: Object.keys(SHIPPING_PARTNERS)[0],
      providerId: email,
      shiprocket: {
        auth: {
          user: email,
          password,
        },
      },
    })
      .then(() => {
        displayNotification({
          type: 'success',
          message: 'Connected Successfully',
          closeTimeout: 2000,
        });
        setTimeout(() => {
          closeModal();
        }, 2000);
      })
      .catch(() => {
        displayNotification({
          type: 'error',
          message: 'The entered credentials are invalid. Please verify & retry',
        });
      });
  }, [setIsCtaEnabled, email, password, createProviders, displayNotification]);

  useEffect(() => {
    checkCtaEnabled(email, password);
  }, [email, password]);
  return (
    <div className="link-account-info display-flex">
      <div className="link-account-instruction">
        <div className="row">
          <div className="filter-item">
            <label htmlFor="email" className="color-black">
              API User Email ID <sup className="magic-checkout-color-red"> *</sup>
            </label>
            <Input
              autoFocus
              name="email"
              placeholder="Email Id of your Shiprocket API User"
              id="email"
              type="email"
              value={email}
              className="shiprocket-api-input"
              onChange={handleInputChange}
            />
          </div>
          <div className="filter-item link-account-instruction">
            <label htmlFor="password" className="color-black">
              API User Password <sup className="magic-checkout-color-red"> *</sup>
            </label>
            <Input
              name="password"
              placeholder="Password of the Shiprocket API User "
              id="password"
              type="password"
              value={password}
              className="shiprocket-api-input"
              onChange={handleInputChange}
            />
          </div>
          <div className="shiprocket-connect-info display-flex">
            <div className="shiprocket-connect-info-icon display-flex flex-center color-white font-12">
              i
            </div>
            <div>
              By sharing your API credentials, you are providing read/write access to Razorpay to
              your Shiprocket account
            </div>
          </div>
        </div>
        <div className="shipping-services-cta-container link-account-instruction display-flex align-center">
          <div className="secondary-cta pointer" onClick={handleSecondaryClick}>
            Back
          </div>
          <div
            className={`primary-cta pointer${
              !isCtaEnabled ? ' shiprocket-connect-cta-disabled' : ''
            }`}
            onClick={handleShiprocketConnect}
          >
            Connect to Shiprocket
          </div>
        </div>
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      createProviders: createShippingProviders,
      displayNotification: showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(ShipRocketForm);
