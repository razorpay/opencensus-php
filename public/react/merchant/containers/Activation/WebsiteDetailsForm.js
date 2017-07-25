import { Component } from 'react';
import { Field } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import Fieldset from 'rzp/ui/Forms/Fieldset';
import { required } from 'rzp/utils/validators';

export default class WebsiteDetailsForm extends Component {
  render() {
    let { handleSubmit, save, saveAndNext, goBack } = this.props;

    /**
     * All template documents mentioned in this file are at
     * https://drive.google.com/drive/u/1/folders/0B1MTSXtR53PfRGNucF82WEdMQkk
     */

    return (
      <form class="form-horizontal" onSubmit={handleSubmit(saveAndNext)}>
        <Fieldset readOnly={this.props.data.locked}>
          <div class="form-group">
            <label class="col-md-3 control-label label-required">
              Website Address
            </label>
            <div class="col-md-9">
              <span class="help-block">
                Example: http://www.example.com/
              </span>

              <Field
                name="business_website"
                component={InputField}
                class="form-control"
                autoFocus={true}
                validate={[required()]}
              />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 control-label label-required">
              About Us URL
            </label>
            <div class="col-md-9">
              <span class="help-block">
                Example: http://www.example.com/aboutus.html
              </span>

              <Field
                name="website_about"
                component={InputField}
                class="form-control"
                validate={[required()]}
              />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 control-label label-required">
              Contact Us URL
            </label>
            <div class="col-md-9">
              <span class="help-block">
                Example: http://www.example.com/contact.html
              </span>

              <Field
                name="website_contact"
                component={InputField}
                class="form-control"
                validate={[required()]}
              />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 control-label label-required">
              Privacy Policy URL
            </label>
            <div class="col-md-9">
              <span class="help-block">
                Download a &nbsp;
                <a
                  class="highlight"
                  href="https://docs.google.com/document/d/1yqqWTE_jfC8F_u9UV9nLq3AUZR2wwpQGJigRJV3YQvg/pub"
                  target="_blank"
                >
                  template here
                </a>
                . Example: http://www.example.com/privacy.html
              </span>

              <Field
                name="website_privacy"
                component={InputField}
                class="form-control"
                validate={[required()]}
              />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 control-label label-required">
              Terms & Conditions URL
            </label>
            <div class="col-md-9">
              <span class="help-block">
                Download a &nbsp;
                <a
                  class="highlight"
                  href="https://docs.google.com/document/d/1bCwt0WccF7oDMBGAGRxtPgUfzqGzkUjtLnnE1JlL2dg/pub"
                  target="_blank"
                >
                  template here
                </a>
                . Example: http://www.example.com/terms.html
              </span>

              <Field
                name="website_terms"
                component={InputField}
                class="form-control"
                validate={[required()]}
              />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 control-label label-required">
              Refund/Cancellation Policy URL
            </label>
            <div class="col-md-9">
              <span class="help-block">
                Download a &nbsp;
                <a
                  class="highlight"
                  href="https://docs.google.com/document/d/1xYM1QHm9S5phnkzyENqJ3KXv37schlsiTp0Id_4IMwE/pub"
                  target="_blank"
                >
                  template here
                </a>
                . Example: http://www.example.com/refund.html
              </span>

              <Field
                name="website_refund"
                component={InputField}
                class="form-control"
                validate={[required()]}
              />
            </div>
          </div>

          <div class="form-group">
            <label class="col-md-3 control-label label-required">
              URL displaying Product Pricing (in INR)
            </label>
            <div class="col-md-9">
              <span class="help-block">
                Any page with product prices in INR. May display a range of prices if not actual price as part of a static pricing policy. Download a &nbsp;
                <a
                  class="highlight"
                  href="https://docs.google.com/document/d/1uibf_AW9DDfNylWgDgCT8EpyWzRF7dZtV8mbEMeP7gs/pub"
                  target="_blank"
                >
                  template here
                </a>
                . Example: http://www.example.com/pricing.html
              </span>

              <Field
                name="website_pricing"
                component={InputField}
                class="form-control"
                validate={[required()]}
              />
            </div>
          </div>

          <div class="form-group">
            <div class="col-md-offset-3 col-md-9">
              <div class="btn-toolbar">
                <AsyncButton
                  type="button"
                  class="btn btn-default pull-left"
                  text="Back"
                  onClick={goBack}
                />

                <AsyncButton
                  class="btn btn-primary pull-right"
                  text="Save & Next"
                  pendingText="Saving..."
                  onClick={handleSubmit(saveAndNext)}
                />

                <AsyncButton
                  type="button"
                  class="btn btn-default pull-right"
                  text="Save"
                  onClick={handleSubmit(save)}
                />
              </div>
            </div>
          </div>
        </Fieldset>
      </form>
    );
  }
}
