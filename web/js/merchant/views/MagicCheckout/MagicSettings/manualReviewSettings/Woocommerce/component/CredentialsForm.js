import React, { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import ModalHeader from 'common/ui/ModalHeader';
import InputField from 'merchant/views/MagicCheckout/common/components/InputField';
import Button from 'common/new-ui/Button';
import { closeModal } from 'merchant_common/reducers/modals';
import { WOOCOMMERCE_REST_API_URL } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const CredentialsForm = ({
  closeModal,
  platform,
  submitCredentials,
  shippingInfo,
  modalDesc,
  customCloseModal,
}) => {
  const [consumerSecret, setConsumerSecret] = useState('');
  const [consumerKey, setConsumerKey] = useState('');
  const [isCtaEnabled, setIsCtaEnabled] = useState(false);

  const disableCtaClass = !isCtaEnabled ? ' cta-disabled' : '';
  const domain = shippingInfo?.split('wp-json')[0];

  const woocommerceDashboardUrl = `${domain}wp-admin/admin.php?page=wc-settings&tab=advanced&section=keys`;

  useEffect(() => {
    setIsCtaEnabled(consumerKey.length && consumerSecret.length);
  }, [consumerSecret, consumerKey]);

  const onSubmit = useCallback(() => {
    setIsCtaEnabled(false);
    submitCredentials({
      api_key: consumerKey,
      api_secret: consumerSecret,
    });
  }, [platform, consumerSecret, consumerKey, setIsCtaEnabled, submitCredentials]);

  return (
    <div className="woocommerce-credentials-form">
      <ModalHeader
        onCloseClick={typeof customCloseModal === 'function' ? customCloseModal : closeModal}
        extraClass="form-title"
      />
      <div className="form-header font-bold color-black">WooCommerce API credentials</div>
      <div className="form-desc">{modalDesc}</div>
      <div className="form-content">
        <div className="row">
          <InputField
            label="Consumer key"
            id="consumer-key"
            value={consumerKey}
            setValue={setConsumerKey}
            autoFocus
            placeholder="Enter consumer key"
            type="text"
          />
          <InputField
            label="Consumer secret"
            id="consumer-secret"
            value={consumerSecret}
            setValue={setConsumerSecret}
            autoFocus={false}
            placeholder="Enter consumer secret"
            type="text"
          />
        </div>
      </div>
      <div className="woocommerce-info display-flex">
        <div className="woocommerce-info-icon display-flex flex-center color-white font-12">i</div>
        <div className="woocommerce-info-content">
          Generate API credentials on{' '}
          {React.createElement(
            'a',
            {
              href: woocommerceDashboardUrl,
              target: '_blank',
              className: 'info-link',
              rel: 'noreferrer noopener',
            },
            'Woocommerce dashboard ',
          )}
          and{' '}
          <a
            href={WOOCOMMERCE_REST_API_URL}
            target="_blank"
            className="info-link"
            rel="noreferrer noopener"
          >
            learn more
          </a>
        </div>
      </div>
      <div className="woocommerce-cta-container">
        <Button.Primary
          type="button"
          onClick={onSubmit}
          className={`woocommerce-form-cta${disableCtaClass}`}
          disabled={!isCtaEnabled}
        >
          Submit
        </Button.Primary>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  shippingInfo: state.magic_settings?.shipping_info,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CredentialsForm);
