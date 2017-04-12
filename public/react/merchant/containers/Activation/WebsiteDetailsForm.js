import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import Alert from 'rzp/ui/Forms/Alert'
import { required, email, phone } from 'rzp/utils/validators'
import { saveStep } from 'merchant/modules/activation'
import { showNotification } from 'merchant/modules/notifications'

@connect(
  (state) => state.activation,
  { saveStep, showNotification }
)
@reduxForm({
  form: 'activationWebsiteDetails',
  destroyOnUnmount: false,
})
export default class WebsiteDetailsForm extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      errors: null
    }
  }

  componentWillMount() {
    if (!this.props.initialized) {
      this.props.initialize(this.props.data)
    }
  }

  save = (props) => {
    return this.props.saveStep(3, props).then((response) => {
      this.props.showNotification({
        type: 'success',
        message: 'Step saved successfully'
      })
      this.props.gotoTab(4)
    }).catch(({ errors }) => {
      this.setState({
        errors
      })
    })
  }

  render() {
    let { handleSubmit } = this.props

    return (
      <div class='panel'>
        <div class='panel-body'>
          <div class='row'>
            <div class='col-md-offset-2 col-md-10'>
              <h4 class='wizard-header'>Website Details</h4>
            </div>
          </div>

          <div class='row'>
            <div class='col-lg-8 col-md-10 col-sm-12'>
              <Alert type='error' message={this.state.errors} />

              <form class='form-horizontal' onSubmit={handleSubmit(this.save)}>
                <fieldset>
                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Website Address</label>
                    <div class='col-md-9'>
                      <span class='help-block'>
                        Example: http://www.website.com/
                      </span>

                      <Field
                        name='business_website'
                        component={InputField}
                        class='form-control'
                        autoFocus={true}
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>About Us URL</label>
                    <div class='col-md-9'>
                      <span class='help-block'>
                        Example: http://www.website.com/aboutus.html
                      </span>

                      <Field
                        name='website_about'
                        component={InputField}
                        class='form-control'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Contact Us URL</label>
                    <div class='col-md-9'>
                      <span class='help-block'>
                        Example: http://www.website.com/contact.html
                      </span>

                      <Field
                        name='website_contact'
                        component={InputField}
                        class='form-control'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Privacy Policy URL</label>
                    <div class='col-md-9'>
                      <span class='help-block'>
                        Download a &nbsp;
                        <a
                          class='highlight'
                          href='https://docs.google.com/document/d/1MpaLoEbx5-cmTB3qfbDadhjsuNCkEA2j13H2ZGx3PAk/edit?usp=sharing'
                          target='_blank'
                        >
                          template here
                        </a>
                        . Example: http://www.website.com/privacy.html
                      </span>

                      <Field
                        name='website_privacy'
                        component={InputField}
                        class='form-control'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Terms &amp; Conditions URL</label>
                    <div class='col-md-9'>
                      <span class='help-block'>
                        Download a &nbsp;
                        <a
                          class='highlight'
                          href='https://docs.google.com/document/d/1wYq6CULlAtBdYrcjEVygQ3uYOplqU-kT1wrxguUKHcI/edit?usp=sharing'
                          target='_blank'
                        >
                          template here
                        </a>
                        . Example: http://www.website.com/terms.html
                      </span>

                      <Field
                        name='website_terms'
                        component={InputField}
                        class='form-control'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Refund/Cancellation Policy URL</label>
                    <div class='col-md-9'>
                      <span class='help-block'>
                        Download a &nbsp;
                        <a
                          class='highlight'
                          href='https://docs.google.com/document/d/1zOrg11NPYSMCxa3KwkOnfPxKRkllzXBdocEQsQN10TM/edit?usp=sharing'
                          target='_blank'
                        >
                          template here
                        </a>
                        . Example: http://www.website.com/refund.html
                      </span>

                      <Field
                        name='website_refund'
                        component={InputField}
                        class='form-control'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>URL displaying Product Pricing (in INR)</label>
                    <div class='col-md-9'>
                      <span class='help-block'>
                        Any page with product prices in INR. May display a range of prices if not actual price as part of a static pricing policy. Download a &nbsp;
                        <a
                          class='highlight'
                          href='https://docs.google.com/document/d/1pcgpjZyeV9fGB-fjo9p9xx4992QeymEMi8oFllcbXsw/edit?usp=sharing'
                          target='_blank'
                        >
                          template here
                        </a>
                        . Example: http://www.website.com/pricing.html
                      </span>

                      <Field
                        name='website_pricing'
                        component={InputField}
                        class='form-control'
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <div class='col-md-offset-3 col-md-9'>
                      <div class='btn-toolbar'>
                        <AsyncButton
                          type='button'
                          class='btn btn-default pull-left'
                          text='Back'
                          onClick={() => this.props.gotoTab(1)}
                        />

                        <AsyncButton
                          class='btn btn-primary pull-right'
                          text='Save & Next'
                          pendingText='Saving...'
                          onClick={handleSubmit(this.save)}
                        />

                        <AsyncButton
                          type='button'
                          class='btn btn-default pull-right'
                          text='Save'
                          onClick={handleSubmit(this.save)}
                        />
                      </div>
                    </div>
                  </div>
                </fieldset>
              </form>
            </div>
          </div>
        </div>
      </div>
    )
  }
}
