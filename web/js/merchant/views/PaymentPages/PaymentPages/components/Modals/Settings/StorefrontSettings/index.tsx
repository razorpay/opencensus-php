import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import {
  StyledModalMask,
  StyledTitle,
  SettingsSection,
  CtaSection,
  Footer,
  StyledForm,
  CustomSlugSection,
} from './styled';
import PluginsAndAddOns from 'merchant/views/PaymentPages/PaymentPages/components/Modals/PluginsAndAddOns';

import { lenientUrl, validateSlug } from 'common/utils/validators';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

interface IStorefrontSettingsProps {
  storefrontEntity: any;
  onSave: (formData) => void;
  onPluginsAndAddOnsSave: (formData) => void;
  onClose: () => void;
  openModal: (data) => void;
  closeModal: () => void;
  isTestMode: boolean;
}

const CUSTOM_URL_INPUT_NAME = 'slug';
const CUSTOM_URL_LABEL = 'URL of this page';
const CUSTOM_URL_RZP_PAGES_URL = 'https://pages.razorpay.com/stores/';
const CUSTOM_URL_INPUT_CLASS = 'Input--vTop';
const CUSTOM_URL_MAX_LENGTH = '30';
const CUSTOM_URL_DISABLED_INFO = (
  <div style={{ marginTop: 4, fontSize: 13 }}>
    Custom URL is only available in <b>Live Mode</b>
  </div>
);

const StorefrontSettings = ({
  storefrontEntity = {},
  onSave,
  onPluginsAndAddOnsSave,
  onClose,
  openModal,
  closeModal,
  isTestMode,
}: IStorefrontSettingsProps): React.ReactElement => {
  const settings = storefrontEntity.settings || {};
  const isPluginConfigured = settings.pp_ga_pixel_tracking_id || settings.pp_fb_pixel_tracking_id;
  const [paymentSuccessMessage, setPaymentSuccessMessage] = useState(
    settings.payment_success_message || '',
  );
  const [paymentSuccessRedirectUrl, setPaymentSuccessRedirectUrl] = useState(
    settings.payment_success_redirect_url || '',
  );
  const [hasPaymentSuccessMessage, setHasPaymentSuccessMessage] = useState(!!paymentSuccessMessage);
  const [hasPaymentSuccessRedirectUrl, setHasPaymentSuccessRedirectUrl] = useState(
    !!paymentSuccessRedirectUrl,
  );
  const [expireBy, setExpireBy] = useState(
    moment(Number(storefrontEntity.expire_by * 1000)) || null,
  );
  const [isSubmitDisabled, setIsSubmitDisabled] = useState(false);

  const handleSubmit = (formData) => {
    const updatedFormData = {
      ...formData,
      expire_by: formData?.expire_by ? formData.expire_by / 1000 : undefined,
    };

    onSave(updatedFormData);
    onClose();
  };

  const handleChange = () => {
    setTimeout(() => {
      const form = document.getElementsByName('page-settings')[0];
      const disableSubmit = form.querySelectorAll('.is-invalid').length;

      setIsSubmitDisabled(!!disableSubmit);
    });
  };

  const handleExpiryDateChange = (newDate) => {
    setExpireBy(newDate);
  };

  const handleSuccessMessageChange = (e) => {
    setPaymentSuccessMessage(e.target.value.replace(/(\r\n|\n|\r)/gm, ''));
  };

  const handleRedirectUrlChange = (e) => {
    setPaymentSuccessRedirectUrl(e.target.value);
  };

  const handleConfigurePlugins = () => {
    openModal({
      size: 'medium',
      className: 'PluginsAndAddOns',
      component: (
        <PluginsAndAddOns
          settings={settings}
          closeModal={closeModal}
          onSave={onPluginsAndAddOnsSave}
        />
      ),
    });
  };

  const validator = (val) => {
    if (!isTestMode) {
      if (val && !validateSlug(val.trim())) {
        return 'Please enter valid Url';
      }

      if (val.length < 4) {
        return 'Url must be at least 4 characters long';
      } else if (val.length > 30) {
        return 'Url must be maximum 30 characters long';
      }
    }
    return '';
  };

  const PluginsBtn = (
    <Button.Transparent type="button" class="Button--Link" onClick={handleConfigurePlugins}>
      <b>{isPluginConfigured ? 'Update' : 'Configure'}</b>
    </Button.Transparent>
  );

  const isSuccessScreen = window.location.pathname?.includes('/success');

  return (
    <StyledModalMask maskClosable={false} isSuccessScreen={isSuccessScreen}>
      <Modal showCloseBtn={false}>
        <ModalContent>
          <StyledTitle>
            <i className="i i-settings-outline mr-8" />
            Page Settings
          </StyledTitle>
          <StyledForm onSubmit={handleSubmit} onChange={handleChange} name="page-settings">
            <CustomSlugSection>
              <Input
                name={CUSTOM_URL_INPUT_NAME}
                class={CUSTOM_URL_INPUT_CLASS}
                label={CUSTOM_URL_LABEL}
                defaultValue={storefrontEntity.slug}
                addonValueBefore={CUSTOM_URL_RZP_PAGES_URL}
                disabled={isTestMode}
                validator={validator}
                maxLength={CUSTOM_URL_MAX_LENGTH}
              />
              {isTestMode && CUSTOM_URL_DISABLED_INFO}
            </CustomSlugSection>
            <div>
              <SettingsSection>
                <input
                  name="expire_by"
                  value={expireBy ? expireBy.valueOf() : ''}
                  readOnly
                  hidden
                />
                <Input.DateTime
                  label="Page Expiry Date"
                  checkboxFieldLabel="No Expiry"
                  class="Input--vTop Input--expiryby"
                  value={expireBy}
                  defaultValue={expireBy}
                  onChange={handleExpiryDateChange}
                  isInline
                />
              </SettingsSection>

              <SettingsSection>
                <div className="InputGroup InputGroup--vTop InputGroup--near Input">
                  <div className="Input-label">Action after successful payment?</div>
                  <div className="Input-content">
                    <Input.Check
                      fieldLabel="Show custom message"
                      defaultValue={paymentSuccessMessage ? '1' : '0'}
                      onChange={(e) => {
                        setHasPaymentSuccessMessage(e.target.value == '1');
                      }}
                    />

                    {hasPaymentSuccessMessage && (
                      <div className="custom-success-msg">
                        <Input.Textarea
                          name="payment_success_message"
                          maxLength="80"
                          value={paymentSuccessMessage}
                          onChange={handleSuccessMessageChange}
                        />
                        <span className="chars-pressed">
                          {`${paymentSuccessMessage ? paymentSuccessMessage.length : '0'} / 80`}
                        </span>
                      </div>
                    )}

                    <Input.Check
                      fieldLabel="Redirect to your website"
                      defaultValue={paymentSuccessRedirectUrl ? '1' : '0'}
                      onChange={(e) => {
                        setHasPaymentSuccessRedirectUrl(e.target.value == '1');
                      }}
                    />

                    {hasPaymentSuccessRedirectUrl && (
                      <Input
                        name="payment_success_redirect_url"
                        validator={lenientUrl('Please enter a valid URL')}
                        value={paymentSuccessRedirectUrl}
                        onChange={handleRedirectUrlChange}
                      />
                    )}
                  </div>
                </div>
              </SettingsSection>

              <SettingsSection>
                <div className="Input-label">Plugins and Add ons</div>
                <CtaSection>
                  <div className="body">
                    {isPluginConfigured ? (
                      <div>
                        Facebook ID: {settings.pp_fb_pixel_tracking_id || '-'}
                        <br />
                        GA ID: {settings.pp_ga_pixel_tracking_id || '-'}
                      </div>
                    ) : (
                      'Add your Facebook Pixel or Google tracking ID to track your page metrics'
                    )}
                  </div>
                  <span className="action">{PluginsBtn}</span>
                </CtaSection>
              </SettingsSection>
            </div>
            <Footer>
              <Button.Transparent type="button" onClick={onClose}>
                Cancel
              </Button.Transparent>
              <Button.Primary type="submit" disabled={isSubmitDisabled}>
                Save
              </Button.Primary>
            </Footer>
          </StyledForm>
        </ModalContent>
      </Modal>
    </StyledModalMask>
  );
};

const mapStateToProps = (state) => ({ isTestMode: state.session.mode === 'test' });

const mapDispatchToProps = (dispatch) => bindActionCreators({ closeModal, openModal }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(StorefrontSettings);
