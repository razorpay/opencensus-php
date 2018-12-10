import { ModalMask, Modal, ModalContent } from 'component/Modal';
import Form from 'component/Form';
import Button, { AsyncBtn } from 'component/Button';
import Input from 'component/Input';

import { DateField } from '../../../PaymentLinks/Edit/EditExpiry';
import { validateSlug } from 'rzp/utils/validators';

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

  render() {
    const { isNew, handleClose, handleAction, isTestMode } = this.props;

    const { slug, theme, expire_by, disableSubmit } = this.state;

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
                    Custom slug is only available in Live Mode
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
                  defaultValue={expire_by || ''}
                  readOnly
                  hidden
                />
                <DateField
                  label="Set Page Expiry Date"
                  className="Input--vTop Input--expiryby"
                  updateDate={this.updateDate}
                  expire_by={expire_by}
                  defaultValue={expire_by}
                  isInline
                />
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
