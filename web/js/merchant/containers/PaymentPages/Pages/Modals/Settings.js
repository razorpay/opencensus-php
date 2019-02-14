import { ModalMask, Modal, ModalContent } from 'component/Modal';
import Form from 'component/Form';
import Button, { AsyncBtn } from 'component/Button';
import Input from 'component/Input';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import { lenientUrl } from 'rzp/utils/validators';
import { DateField } from '../../../PaymentLinks/Edit/EditExpiry';
import { validateSlug } from 'rzp/utils/validators';

import PPEmbedButtonView from '../Modals/EmbedButton';

export default class extends React.Component {
  state = {
    expire_by: this.props.paymentPageEntity.expire_by
      ? moment(Number(this.props.paymentPageEntity.expire_by))
      : undefined,
    theme:
      this.props.paymentPageEntity.settings &&
      this.props.paymentPageEntity.settings.theme === 'dark'
        ? '0'
        : '1',
    slug: this.props.paymentPageEntity.slug || '',
  };

  updateDate = newDate => {
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
      component: (
        <PPEmbedButtonView shortUrl={this.props.paymentPageEntity.short_url} />
      ),
    });
  };

  onSuccessMsgChange = e => {
    this.setState({
      custom_success_message: e.target.value,
    });
  };

  render() {
    const {
      isNew,
      handleClose,
      handleAction,
      isTestMode,
      paymentPageEntity,
    } = this.props;

    const { slug, theme, expire_by, disableSubmit } = this.state;

    const EmbedBtn = (
      <Button.Transparent
        type="button"
        class="Button--Link"
        disabled={
          !(
            paymentPageEntity.id &&
            typeof paymentPageEntity.title !== 'undefined'
          )
        }
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
            <Form
              class="Settings-form"
              onSubmit={handleAction}
              onChange={this.onChange}
            >
              <div class="settings-section">
                <Input
                  name="slug"
                  className="Input--vTop"
                  label="Choose custom URL for this page"
                  defaultValue={slug}
                  addonValueBefore="https://pages.razorpay.com/"
                  disabled={isTestMode}
                  validator={val => {
                    if (val) {
                      if (!validateSlug(val.trim())) {
                        return 'Please enter valid Url';
                      }

                      if (val.length < 4) {
                        return 'Url must be atleast 4 characters long';
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
                  className="Input--vTop Input--theme"
                  defaultValue={theme}
                />
              </div>
              <div class="settings-section">
                <input
                  name="expire_by"
                  value={expire_by || ''}
                  readOnly
                  hidden
                />
                <DateField
                  label="Page Expiry Date"
                  className="Input--vTop Input--expiryby"
                  updateDate={this.updateDate}
                  expire_by={expire_by}
                  defaultValue={expire_by}
                  isInline
                />
              </div>

              <div class="settings-section">
                <div class="InputGroup InputGroup--vTop InputGroup--near Input">
                  <div class="Input-label">
                    Action after successful payment?
                  </div>
                  <div class="Input-content">
                    <Input.Check
                      fieldLabel="Show custom message"
                      onChange={e => {
                        const isChecked = e.target.value == '1';

                        this.setState({ _hasCustomMessage: isChecked }, () => {
                          if (this.state._hasCustomMessage) {
                            document
                              .getElementsByName('payment_success_message')[0]
                              .focus();
                          }
                        });
                      }}
                    />

                    {this.state._hasCustomMessage && (
                      <div class="custom-success-msg">
                        <Input.Textarea
                          name="payment_success_message"
                          maxLength="80"
                          onChange={this.onSuccessMsgChange}
                        />
                        <span class="chars-pressed">
                          {(this.state.custom_success_message
                            ? this.state.custom_success_message.length
                            : '0') + ' /80'}
                        </span>
                      </div>
                    )}

                    <Input.Check
                      fieldLabel="Redirect to your website"
                      onChange={e => {
                        const isChecked = e.target.value == '1';

                        this.setState({ _hasRedirectUrl: isChecked }, () => {
                          if (this.state._hasRedirectUrl) {
                            document
                              .getElementsByName(
                                'payment_success_redirect_url'
                              )[0]
                              .focus();
                          }
                        });
                      }}
                    />

                    {this.state._hasRedirectUrl && (
                      <Input
                        name="payment_success_redirect_url"
                        validator={lenientUrl('Please enter a valid URL')}
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
                        Your customers can pay from your website by clicking on
                        this Payment Button
                      </PopoverBody>
                    </Popover>
                  </span>
                  {!(
                    paymentPageEntity.id &&
                    typeof paymentPageEntity.title !== 'undefined'
                  ) ? (
                    <span class="help-content" style={{ float: 'right' }}>
                      <span>{EmbedBtn}</span>
                      <Popover
                        align="top"
                        theme="dark"
                        parentQuerySelector={`.Modal-mask--paymentpages-settings .Modal-body`}
                      >
                        <PopoverBody>
                          You can customize Embed Button after creating Payment
                          Page
                        </PopoverBody>
                      </Popover>
                    </span>
                  ) : (
                    <span style={{ float: 'right' }}>{EmbedBtn}</span>
                  )}
                </div>
              </div>
              <footer>
                <Button.Transparent type="button" onClick={handleClose}>
                  Cancel
                </Button.Transparent>
                <Button.Primary type="submit" disabled={disableSubmit}>
                  {isNew ? 'Save and Publish' : 'Save'}
                </Button.Primary>
              </footer>
            </Form>
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}
