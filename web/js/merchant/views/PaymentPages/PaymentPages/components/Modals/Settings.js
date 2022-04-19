import React from 'react';
import moment from 'moment';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Alert from 'common/new-ui/Alert';
import { lenientUrl, validateSlug } from 'common/utils/validators';
import { DocLink } from 'merchant/components/DocsLink';
import { trackPageSettingsData } from '../../ga';
import track from '../../Wysiwyg/track';

import CreateEmbedButton from 'merchant/views/PaymentPages/PaymentPages/components/Modals/CreateEmbedButton';
import PluginsAndAddOns from './PluginsAndAddOns';
import ShiprocketImage from '../../../../../../../css/assets/payment_pages/shiprocket.svg';

export default class PaymentPageSettings extends React.Component {
  state = this.initState();

  initState() {
    const paymentPageEntity = this.props.paymentPageEntity;
    const settings = paymentPageEntity.settings;

    return {
      expire_by: paymentPageEntity.expire_by
        ? moment(Number(paymentPageEntity.expire_by))
        : undefined,
      theme: settings && settings.theme === 'dark' ? '0' : '1',
      slug: paymentPageEntity.slug || '',
      payment_success_message: settings ? settings.payment_success_message : '',
      payment_success_redirect_url: settings ? settings.payment_success_redirect_url : '',
      _hasSuccessMsg: settings && settings.payment_success_message,
      _hasRedirectUrl: settings && settings.payment_success_redirect_url,
    };
  }

  updateDate = (newDate) => {
    this.setState({ expire_by: newDate });

    /*
      newDate get's passed only when 'No Expiry' is unchecked
      AND a date is selected in the date picker
    */
    if (newDate) {
      track.settings.selectExpiryDate();
    }
    track.settings.noExpiry(!newDate);
  };

  onChange = () => {
    setTimeout(() => {
      const form = document.getElementsByClassName('Settings-form')[0];
      const disableSubmit = form.querySelectorAll('.is-invalid').length;

      this.setState({ disableSubmit });
    });
  };

  openEmbedButtonView = () => {
    this.props.openModal({
      size: 'small',
      component: <CreateEmbedButton id={this.props.paymentPageEntity.id} />,
    });

    track.settings.clickCreateHyperlinkButton();
  };

  openConfigurePluginsView = () => {
    this.props.openModal({
      size: 'medium',
      className: 'PluginsAndAddOns',
      component: <PluginsAndAddOns />,
    });

    track.settings.clickConfigurePlugins();
  };

  onSuccessMsgChange = (e) => {
    this.setState({
      payment_success_message: e.target.value.replace(/(\r\n|\n|\r)/gm, ''),
    });
  };

  onSubmit = (formData) => {
    track.settings.save(
      !!formData.expire_by,
      !!formData.payment_success_message,
      !!formData.payment_success_redirect_url,
    );
    this.props.handleAction(formData);

    /*
     * Preparing Tracking data
     * */
    const trackData = [];

    if (formData.expire_by) {
      trackData.push('expire_by');
    }

    // Sending slug only in edit mode
    if (
      formData.slug &&
      this.props.paymentPageEntity.id &&
      formData.slug !== this.props.paymentPageEntity.slug
    ) {
      trackData.push('slug');
    }

    if (formData.payment_success_message) {
      trackData.push('payment_success_message');
    }

    if (formData.payment_success_redirect_url) {
      trackData.push('payment_success_redirect_url');
    }

    trackPageSettingsData(this.props.isNew ? 'Update' : 'Save', trackData);
  };

  render() {
    const { handleClose, isTestMode, paymentPageEntity, isShiprocket, isPPShiprocket } = this.props;

    const {
      slug,
      payment_success_message,
      payment_success_redirect_url,
      _hasSuccessMsg,
      _hasRedirectUrl,
      theme,
      expire_by,
      disableSubmit,
    } = this.state;

    const EmbedBtn = (
      <Button.Transparent
        type="button"
        class="Button--Link"
        disabled={!(paymentPageEntity.id && typeof paymentPageEntity.title !== 'undefined')}
        onClick={this.openEmbedButtonView}
      >
        <b>Create</b>
      </Button.Transparent>
    );

    const isPluginConfigured =
      paymentPageEntity.settings &&
      (paymentPageEntity.settings.pp_ga_pixel_tracking_id ||
        paymentPageEntity.settings.pp_fb_pixel_tracking_id);

    const PluginsBtn = (
      <Button.Transparent
        type="button"
        class="Button--Link"
        onClick={this.openConfigurePluginsView}
      >
        <b>{isPluginConfigured ? 'Update' : 'Configure'}</b>
      </Button.Transparent>
    );

    return (
      <ModalMask maskClosable={false} class="paymentpages-settings">
        <Modal showCloseBtn={false}>
          <ModalContent>
            <div class="main-title">
              <i className="i i-settings-outline mr-8" />
              Page Settings
            </div>
            <Form class="Settings-form" onSubmit={this.onSubmit} onChange={this.onChange}>
              <div class="Settings-form--body">
                <div class="settings-section custom-url" tabIndex={-1}>
                  <Input
                    name="slug"
                    class="Input--vTop"
                    label="Choose custom URL for this page"
                    defaultValue={slug}
                    addonValueBefore="https://pages.razorpay.com/"
                    disabled={isTestMode}
                    validator={(val) => {
                      const isEditMode = !!this.props.paymentPageEntity.id;
                      const toValidate = !isTestMode && isEditMode; // Validate only when live mode and editing page

                      if (toValidate) {
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
                    }}
                    onBlur={track.settings.enterCustomUrl}
                  />
                  {isTestMode && (
                    <div style={{ marginTop: 4, fontSize: 13 }}>
                      Custom slug is only available in <b>Live Mode</b>
                    </div>
                  )}
                </div>
                <div class="settings-section">
                  <Input.Radio
                    name="theme"
                    label="Theme"
                    options={['Dark', 'Light']}
                    class="Input--vTop Input--theme"
                    defaultValue={theme}
                  />
                </div>
                <div class="settings-section">
                  <input name="expire_by" value={expire_by || ''} readOnly hidden />
                  <Input.DateTime
                    label="Page Expiry Date"
                    checkboxFieldLabel="No Expiry"
                    class="Input--vTop Input--expiryby"
                    value={expire_by}
                    defaultValue={expire_by}
                    onChange={this.updateDate}
                    isInline
                  />
                </div>

                <div class="settings-section">
                  <div class="InputGroup InputGroup--vTop InputGroup--near Input">
                    <div class="Input-label">Action after successful payment?</div>
                    <div class="Input-content">
                      <Input.Check
                        fieldLabel="Show custom message"
                        defaultValue={_hasSuccessMsg ? '1' : '0'}
                        onChange={(e) => {
                          const isChecked = e.target.value == '1';

                          this.setState({ _hasSuccessMsg: isChecked }, () => {
                            if (isChecked) {
                              document.getElementsByName('payment_success_message')[0].focus();
                            }
                          });

                          track.settings.checkCustomMessage(isChecked);
                        }}
                      />

                      {_hasSuccessMsg && (
                        <div class="custom-success-msg">
                          <Input.Textarea
                            name="payment_success_message"
                            maxLength="80"
                            value={payment_success_message}
                            onChange={this.onSuccessMsgChange}
                          />
                          <span class="chars-pressed">
                            {`${
                              payment_success_message ? payment_success_message.length : '0'
                            } / 80`}
                          </span>
                        </div>
                      )}

                      <Input.Check
                        fieldLabel="Redirect to your website"
                        defaultValue={_hasRedirectUrl ? '1' : '0'}
                        onChange={(e) => {
                          const isChecked = e.target.value == '1';

                          this.setState({ _hasRedirectUrl: isChecked }, () => {
                            if (isChecked) {
                              document.getElementsByName('payment_success_redirect_url')[0].focus();
                            }
                          });

                          track.settings.checkRedirect(isChecked);
                        }}
                      />

                      {_hasRedirectUrl && (
                        <Input
                          name="payment_success_redirect_url"
                          validator={lenientUrl('Please enter a valid URL')}
                          defaultValue={payment_success_redirect_url}
                        />
                      )}
                    </div>
                  </div>
                </div>

                <div class="settings-section">
                  <b>Get Hyperlink Button</b>
                  <div class="cta-section">
                    <div class="body">
                      Put a hyperlink button on your website
                      <span class="help-content">
                        <i class="i i-info-outline" style={{ marginLeft: 4 }} />
                        <Popover
                          align="top"
                          theme="dark"
                          parentQuerySelector=".Modal-mask--paymentpages-settings .Modal-body"
                        >
                          <PopoverBody>
                            Your customers can pay from your website by clicking on this Payment
                            Button
                          </PopoverBody>
                        </Popover>
                      </span>
                    </div>
                    {!(paymentPageEntity.id && typeof paymentPageEntity.title !== 'undefined') ? (
                      <span class="help-content action">
                        <span>{EmbedBtn}</span>
                        <Popover
                          align="top"
                          theme="dark"
                          parentQuerySelector=".Modal-mask--paymentpages-settings .Modal-body"
                        >
                          <PopoverBody>
                            You can customize Embed Button after creating Payment Page
                          </PopoverBody>
                        </Popover>
                      </span>
                    ) : (
                      <span class="action">{EmbedBtn}</span>
                    )}
                  </div>
                </div>
                <div class="settings-section">
                  <div class="Input-label">Plugins and Add ons</div>
                  <div class="cta-section">
                    <div class="body">
                      {isPluginConfigured ? (
                        <div>
                          Facebook ID: {paymentPageEntity.settings.pp_fb_pixel_tracking_id || '-'}
                          <br />
                          GA ID: {paymentPageEntity.settings.pp_ga_pixel_tracking_id || '-'}
                        </div>
                      ) : (
                        'Add your Facebook Pixel or Google tracking ID to track your page metrics'
                      )}
                    </div>
                    <span class="action">{PluginsBtn}</span>
                  </div>
                </div>
                {isPPShiprocket && (
                  <div class="settings-section shiprocket-section">
                    <div class="Input-label">
                      <img src={ShiprocketImage} alt="shiprocket-logo" />
                      Create orders on Shiprocket{' '}
                      <span class="badge bg-success hidden-xs m-r">New</span>
                    </div>
                    <div class="cta-section">
                      <div class="body">
                        After your customers pay on this page, automatically create orders on
                        Shiprocket{' '}
                        <span>
                          <i class="i i-info-outline" style={{ marginLeft: 4 }} />
                          <Popover
                            align="top"
                            theme="dark"
                            parentQuerySelector=".Modal-mask--paymentpages-settings .Modal-body"
                          >
                            <PopoverBody>
                              Shiprocket is an eCommerce shipping solution, known for low shipping
                              rates and wide reach
                            </PopoverBody>
                          </Popover>
                        </span>
                      </div>
                      <span class="action">
                        <Button.Transparent
                          type="button"
                          class="Button--Link"
                          onClick={this.props.handleShiprocket}
                        >
                          <b>{!isShiprocket ? 'Enable' : 'Disable'}</b>
                        </Button.Transparent>
                      </span>
                    </div>
                    <Alert.Warning>
                      Note - you also need to add <b>Razorpay Payment pages</b> channel on your{' '}
                      <a
                        href="https://app.shiprocket.in/register?utm_source=Razorpay&utm_medium=In-Product&utm_campaign=PaymentPages&utm_content=Razorpay-In-product"
                        target="_blank"
                        rel="noreferrer noopener"
                        onClick={track.settings.clickShiprocketDashboard}
                      >
                        Shiprocket dashboard <i className="i i-external-link" />
                      </a>
                    </Alert.Warning>
                    <div>
                      Need help? Refer to our{' '}
                      <DocLink
                        href="https://razorpay.com/docs/payments/payment-pages/plugins-add-ons/shiprocket"
                        target="_blank"
                        onClick={track.wysiwyg.clickShiprocketDocsLink.bind(null, 'settings')}
                        rel="noreferrer noopener"
                      >
                        Shiprocket integration docs <i class="i i-external-link" />
                      </DocLink>
                    </div>
                  </div>
                )}
              </div>
              <footer>
                <Button.Transparent
                  type="button"
                  onClick={() => {
                    track.settings.close();
                    handleClose();
                  }}
                >
                  Cancel
                </Button.Transparent>
                <Button.Primary type="submit" disabled={disableSubmit}>
                  Save
                </Button.Primary>
              </footer>
            </Form>
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}
