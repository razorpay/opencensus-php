import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { lenientUrl, validateSlug } from 'common/utils/validators';
import { DateField } from 'merchant/views/PaymentLinks/PaymentLinks/components/Edit/EditExpiry';
import { trackPageSettingsData } from '../../ga';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import CreateEmbedButton from 'merchant/views/PaymentPages/PaymentPages/components/Modals/CreateEmbedButton';

export default class extends React.Component {
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
    analyticsTrack({
      objectName: 'settings expiry',
      actionName: 'added',
      screen: 'create payment page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.setState({ expire_by: newDate });
  };

  onChange = ({ target }) => {
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
  };

  onSuccessMsgChange = (e) => {
    this.setState({
      payment_success_message: e.target.value.replace(/(\r\n|\n|\r)/gm, ''),
    });
  };

  onSubmit = (formData) => {
    analyticsTrack({
      objectName: 'settings',
      actionName: 'saved',
      screen: 'create payment page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
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

    if (typeof formData.theme !== 'undefined') {
      if (formData.theme === '0') {
        trackData.push('theme: dark');
      } else {
        trackData.push('theme: light');
      }
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
    const { handleClose, isTestMode, paymentPageEntity } = this.props;

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

    return (
      <ModalMask maskClosable={false} class="paymentpages-settings">
        <Modal showCloseBtn={false}>
          <ModalContent>
            <div class="main-title">Page Settings</div>
            <Form class="Settings-form" onSubmit={this.onSubmit} onChange={this.onChange}>
              <div class="settings-section">
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
                  }}
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
                          {(payment_success_message ? payment_success_message.length : '0') +
                            ' / 80'}
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
                <b>Embed Payment Button</b>
                <div>
                  Put a payment button on your website
                  <span class="help-content">
                    <i class="i i-info-outline" style={{ marginLeft: 4 }} />
                    <Popover
                      align="top"
                      theme="dark"
                      parentQuerySelector={`.Modal-mask--paymentpages-settings .Modal-body`}
                    >
                      <PopoverBody>
                        Your customers can pay from your website by clicking on this Payment Button
                      </PopoverBody>
                    </Popover>
                  </span>
                  {!(paymentPageEntity.id && typeof paymentPageEntity.title !== 'undefined') ? (
                    <span class="help-content" style={{ float: 'right' }}>
                      <span>{EmbedBtn}</span>
                      <Popover
                        align="top"
                        theme="dark"
                        parentQuerySelector={`.Modal-mask--paymentpages-settings .Modal-body`}
                      >
                        <PopoverBody>
                          You can customize Embed Button after creating Payment Page
                        </PopoverBody>
                      </Popover>
                    </span>
                  ) : (
                    <span style={{ float: 'right' }}>{EmbedBtn}</span>
                  )}
                </div>
              </div>
              <footer>
                <Button.Transparent
                  type="button"
                  onClick={() => {
                    analyticsTrack({
                      objectName: 'settings',
                      actionName: 'closed',
                      screen: 'create payment page',
                      properties: {
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
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
