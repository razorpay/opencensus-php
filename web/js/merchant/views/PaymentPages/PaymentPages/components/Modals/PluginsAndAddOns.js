import React from 'react';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import { ModalContent } from 'common/new-ui/Modal';

import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';

import FbPixelImage from 'assets/payment_pages/fb-pixel-logo.svg';
import GaImage from 'assets/payment_pages/ga-logo.svg';

const FB_PIXEL_CTA_LINK =
  'https://www.facebook.com/business/help/952192354843755?id=1205376682832142';
const GA_CTA_LINK =
  'https://support.google.com/analytics/answer/1008080?hl=en#zippy=%2Cweb-hosting-service-you-dont-control-the-page-code';

const validateGaId = (value) => {
  // regex for universal analytics IDs (Older version of GA)
  const regexForUA = new RegExp(/(UA|YT|MO)-\d+-\d+/i);
  // regex for GA 4 IDs
  const regexForGA4 = new RegExp(/^G-[A-Z0-9]{8,12}$/i);

  if (value && !regexForUA.test(value) && !regexForGA4.test(value)) {
    return 'This does not look like a valid ID';
  }
  return '';
};

const validateFbId = (value) => {
  const regex = new RegExp('^[0-9]+$');
  if (value && !regex.test(value)) {
    return 'This does not look like a valid ID';
  }
  return '';
};

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
    track.plugins.save(formData.pp_fb_pixel_tracking_id, formData.pp_ga_pixel_tracking_id);

    const data = {
      pp_fb_pixel_tracking_id: formData.pp_fb_pixel_tracking_id,
      pp_ga_pixel_tracking_id: (formData.pp_ga_pixel_tracking_id || '').toUpperCase(),
      pp_fb_event_add_to_cart_enabled: formData.pp_fb_event_add_to_cart_enabled ? '1' : '0',
      pp_fb_event_initiate_payment_enabled: formData.pp_fb_event_initiate_payment_enabled
        ? '1'
        : '0',
      pp_fb_event_payment_complete_enabled: formData.pp_fb_event_payment_complete_enabled
        ? '1'
        : '0',
    };

    this.props.onSave(data);
    this.props.closeModal();
  };

  render() {
    const {
      pp_fb_pixel_tracking_id,
      pp_ga_pixel_tracking_id,
      pp_fb_event_add_to_cart_enabled,
      pp_fb_event_initiate_payment_enabled,
      pp_fb_event_payment_complete_enabled,
    } = this.state;

    return (
      <ModalContent>
        <div className="main-title">
          <div className="heading">Plugins and Add-ons</div>
          <div>
            Add your Facebook Pixel or Google tracking ID below to track your page metrics.{' '}
          </div>
        </div>
        <Form onSubmit={this.handleSubmit}>
          <div className="section-wrapper">
            <div className="section">
              <div className="section-title">
                <img src={FbPixelImage} alt="FB Pixel Logo" />
                Facebook Pixel
              </div>
              <div className="section-body">
                <Input.Group label="Facebook Pixel ID">
                  <Input
                    autoRender
                    name="pp_fb_pixel_tracking_id"
                    maxLength="32"
                    placeholder="Add ID here"
                    defaultValue={pp_fb_pixel_tracking_id}
                    validator={validateFbId}
                    onBlur={track.settings.enterFBPixel}
                  />
                </Input.Group>
                <span className="help-text">Tracking ID is a string like 1234567890.</span>
                <Input.Group label="Metrics to track">
                  <Input.Check fieldLabel="Page Views" defaultValue="1" disabled />
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
                <span className="help-text">
                  To learn more about Pixel ID and how to create one using Facebook Ads manager
                  account,{' '}
                  <a href={FB_PIXEL_CTA_LINK} target="_blank" rel="noopener noreferrer">
                    click here.
                    <i className="i i-external-link" />
                  </a>
                </span>
              </div>
            </div>
            <div className="section">
              <div className="section-title">
                <img src={GaImage} alt="GA Logo" />
                Google Analytics
              </div>
              <div className="section-body">
                <Input.Group label="Tracking ID">
                  <Input
                    autoRender
                    name="pp_ga_pixel_tracking_id"
                    maxLength="32"
                    placeholder="Add ID here"
                    defaultValue={pp_ga_pixel_tracking_id}
                    validator={validateGaId}
                    onBlur={track.settings.enterGAPixel}
                  />
                </Input.Group>
                <span className="help-text">
                  Tracking ID is a string like UA-000000-2 or G-0A1BC2DE.
                </span>
                <br />
                <br />
                <span className="help-text">
                  To learn more about Tracking ID and how to create one using Google Analytics
                  account,{' '}
                  <a href={GA_CTA_LINK} target="_blank" rel="noopener noreferrer">
                    click here.
                    <i className="i i-external-link" />
                  </a>
                </span>
              </div>
            </div>
          </div>
          <footer>
            <Button.Transparent className="Cancel-btn" type="button" onClick={this.props.closeModal}>
              Cancel
            </Button.Transparent>

            <Button.Primary className="Save-btn" type="submit">
              Save
            </Button.Primary>
          </footer>
        </Form>
      </ModalContent>
    );
  }
}
