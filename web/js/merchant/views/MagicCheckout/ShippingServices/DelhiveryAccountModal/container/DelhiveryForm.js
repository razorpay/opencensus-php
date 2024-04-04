import { useState, useEffect, useCallback } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import Input from 'common/new-ui/Input';
import { createShippingProviders } from 'merchant/reducers/magicCheckout/shipping_services/actions';
import { showNotification } from 'merchant_common/reducers/notifications';
import { SHIPPING_PARTNERS } from 'merchant/views/MagicCheckout/ShippingServices/constants';

const DelhiveryForm = ({ closeModal, createProviders, showNotification, user }) => {
  const [token, setToken] = useState('');
  const [isCtaEnabled, setIsCtaEnabled] = useState(false);
  const [error, setError] = useState(false);

  const errorClass = error ? ' delhivery-api-error-input' : '';
  const disableCtaClass = !isCtaEnabled ? ' delhivery-connect-cta-disabled' : '';

  useEffect(() => {
    setError(false);
    setIsCtaEnabled(token.length);
  }, [token]);

  const handleConnect = useCallback(() => {
    setIsCtaEnabled(false);
    createProviders({
      providerType: Object.keys(SHIPPING_PARTNERS)[1],
      providerId: user?.id,
      delhivery: {
        api_key: token,
      },
    })
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Connected Successfully',
          closeTimeout: 2000,
        });
        setTimeout(() => {
          setError(false);
          closeModal();
        }, 2000);
      })
      .catch(() => {
        setError(true);
        showNotification({
          type: 'error',
          message: 'Incorrect token number. Try again.',
        });
      });
  }, [token, setError, setIsCtaEnabled, createProviders, showNotification, user]);

  return (
    <>
      <ModalHeader
        onCloseClick={closeModal}
        title="Link Delhivery account"
        extraClass="delhivery-form-title"
      />
      <div className="delhivery-form-content">
        <div className="row">
          <div className="filter-item">
            <label for="token" className="color-black">
              Production Authentication token <sup className="magic-checkout-color-red"> *</sup>
            </label>
            <Input
              autoFocus
              name="token"
              placeholder="Enter production authentication token"
              id="token"
              type="text"
              value={token}
              className={`delhivery-api-input${errorClass}`}
              onChange={(e) => setToken(e.target.value)}
            />
          </div>
        </div>
        <div className="shipping-service-cta-container">
          <div
            className={`primary-cta delhivery-form-cta${disableCtaClass}`}
            onClick={handleConnect}
          >
            Connect
          </div>
        </div>
      </div>
    </>
  );
};

const mapStateToProps = (state) => {
  return { user: state.session.user };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      showNotification,
      createProviders: createShippingProviders,
    },
    dispatch,
  );
export default connect(mapStateToProps, mapDispatchToProps)(DelhiveryForm);
