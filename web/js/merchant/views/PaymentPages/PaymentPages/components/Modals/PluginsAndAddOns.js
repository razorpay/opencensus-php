import { connect } from 'react-redux';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';

import { closeModal } from 'merchant_common/reducers/modals';
import { updateData } from 'merchant/reducers/wysiwyg';

const FB_PIXEL_CTA_LINK =
  'https://www.facebook.com/business/help/952192354843755?id=1205376682832142';
const GA_CTA_LINK =
  'https://support.google.com/analytics/answer/1008080?hl=en#zippy=%2Cweb-hosting-service-you-dont-control-the-page-code';

const validateGaId = (value) => {
  var regex = new RegExp(/(UA|YT|MO)-\d+-\d+/i);
  if (value && !regex.test(value)) {
    return 'This does not look like a valid ID';
  }
};

const validateFbId = (value) => {
  var regex = new RegExp('^[0-9]+$');
  if (value && !regex.test(value)) {
    return 'This does not look like a valid ID';
  }
};
@connect(
  (state) => ({
    settings: state.wysiwyg.paymentPageEntity.settings,
  }),
  {
    closeModal,
    updateData,
  },
)
export default class PluginsAndAddOns extends React.Component {
  state = {
    pp_fb_pixel_tracking_id: this.props.settings.pp_fb_pixel_tracking_id || '',
    pp_ga_pixel_tracking_id: this.props.settings.pp_ga_pixel_tracking_id || '',
    pp_fb_event_add_to_cart_enabled: this.props.settings.pp_fb_event_add_to_cart_enabled || '0',
    pp_fb_event_initiate_payment_enabled:
      this.props.settings.pp_fb_event_initiate_payment_enabled || '0',
    pp_fb_event_payment_complete_enabled:
      this.props.settings.pp_fb_event_payment_complete_enabled || '0',
  };

  handleSubmit = (formData) => {
    const data = {};

    data.settings = {
      pp_fb_pixel_tracking_id: formData.pp_fb_pixel_tracking_id,
      pp_ga_pixel_tracking_id: formData.pp_ga_pixel_tracking_id,
      pp_fb_event_add_to_cart_enabled: formData.pp_fb_event_add_to_cart_enabled ? '1' : '0',
      pp_fb_event_initiate_payment_enabled: formData.pp_fb_event_initiate_payment_enabled
        ? '1'
        : '0',
      pp_fb_event_payment_complete_enabled: formData.pp_fb_event_payment_complete_enabled
        ? '1'
        : '0',
    };

    this.props.updateData(data);
    this.props.closeModal();
  };

  render() {
    let {
      pp_fb_pixel_tracking_id,
      pp_ga_pixel_tracking_id,
      pp_fb_event_add_to_cart_enabled,
      pp_fb_event_initiate_payment_enabled,
      pp_fb_event_payment_complete_enabled,
    } = this.state;

    return (
      <div class="PopOver--Modal">
        <div class="main-title">
          <div class="heading">Plugins and Add-ons</div>
          <div>
            Add your Facebook Pixel or Google tracking ID below to track your page metrics.{' '}
          </div>
        </div>
        <Form onSubmit={this.handleSubmit}>
          <div class="section">
            <div class="section-title">
              <img src="/dist/css/assets/payment_pages/fb-pixel-logo.svg" alt="FB Pixel Logo" />
              Facebook Pixel
            </div>
            <div class="section-body">
              <Input.Group label="Facebook Pixel ID">
                <Input
                  autoRender
                  name="pp_fb_pixel_tracking_id"
                  maxLength="32"
                  placeholder="Add ID here"
                  defaultValue={pp_fb_pixel_tracking_id}
                  validator={validateFbId}
                />
              </Input.Group>
              <span class="help-text">Tracking ID is a string like 1234567890.</span>
              <Input.Group label="Metrics to track">
                <Input.Check fieldLabel="Page Views" defaultValue={'1'} disabled />
                <Input.Check
                  fieldLabel="Add to Cart"
                  name="pp_fb_event_add_to_cart_enabled"
                  defaultValue={pp_fb_event_add_to_cart_enabled}
                />
                <Input.Check
                  fieldLabel="Initiate Payment"
                  name="pp_fb_event_initiate_payment_enabled"
                  defaultValue={pp_fb_event_initiate_payment_enabled}
                />
                <Input.Check
                  fieldLabel="Payment Complete"
                  name="pp_fb_event_payment_complete_enabled"
                  defaultValue={pp_fb_event_payment_complete_enabled}
                />
              </Input.Group>
              <br />
              <span class="help-text">
                To learn more about Pixel ID and how to create one using Facebook Ads manager
                account,{' '}
                <a href={FB_PIXEL_CTA_LINK} target="_blank" rel="noopener">
                  click here.<i class="i i-external-link"></i>
                </a>
              </span>
            </div>
          </div>
          <div class="section">
            <div class="section-title">
              <img src="/dist/css/assets/payment_pages/ga-logo.svg" alt="GA Logo" />
              Google Analytics
            </div>
            <div class="section-body">
              <Input.Group label="Tracking ID">
                <Input
                  autoRender
                  name="pp_ga_pixel_tracking_id"
                  maxLength="32"
                  placeholder="Add ID here"
                  defaultValue={pp_ga_pixel_tracking_id}
                  validator={validateGaId}
                />
              </Input.Group>
              <span class="help-text">Tracking ID is a string like UA-000000-2.</span>
              <br />
              <br />
              <span class="help-text">
                To learn more about Tracking ID and how to create one using Google Analytics
                account,{' '}
                <a href={GA_CTA_LINK} target="_blank" rel="noopener">
                  click here.<i class="i i-external-link"></i>
                </a>
              </span>
            </div>
          </div>
          <span class="Modal-actions">
            <Button.Transparent class="Cancel-btn" type="button" onClick={this.props.closeModal}>
              <span>&times;</span>
              Cancel
            </Button.Transparent>

            <Button.Transparent class="Save-btn" type="submit">
              <span class="icon i-check" />
              Save
            </Button.Transparent>
          </span>
        </Form>
      </div>
    );
  }
}
