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
  form: 'activationContactDetails',
  destroyOnUnmount: false,
})
export default class ContactDetailsForm extends Component {
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
    return this.props.saveStep(1, props).then((response) => {
      this.props.showNotification({
        type: 'success',
        message: 'Step saved successfully'
      })
      this.props.gotoTab(2)
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
              <h4 class='wizard-header'>Contact Details</h4>
            </div>
          </div>

          <div class='row'>
            <div class='col-lg-8 col-md-10 col-sm-12'>
              <Alert type='error' message={this.state.errors} />

              <form class='form-horizontal' onSubmit={handleSubmit(this.save)}>
                <fieldset>
                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Contact Name</label>
                    <div class='col-md-9'>
                      <Field
                        name='contact_name'
                        component={InputField}
                        class='form-control'
                        placeholder='Contact Name'
                        autoFocus={true}
                        validate={[
                          required()
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Email</label>
                    <div class='col-md-9'>
                      <Field
                        name='contact_email'
                        component={InputField}
                        class='form-control'
                        placeholder='Email'
                        validate={[
                          required(),
                          email('Invalid Email')
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Transaction Report Email</label>
                    <div class='col-md-9'>
                      <Field
                        name='transaction_report_email'
                        component={InputField}
                        class='form-control'
                        placeholder='Email'
                        validate={[
                          required(),
                          email('Invalid Email')
                        ]}
                      />
                      <small class='help-block'>
                        <i class='fa fa-info-circle'></i>
                        <span>All payment related reports will be sent to this email address</span>
                      </small>
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Mobile</label>
                    <div class='col-md-9'>
                      <Field
                        name='contact_mobile'
                        component={InputField}
                        class='form-control'
                        placeholder='Mobile'
                        validate={[
                          required(),
                          phone('Invalid phone')
                        ]}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label'>Landline</label>
                    <div class='col-md-9'>
                      <Field
                        name='contact_landline'
                        component={InputField}
                        class='form-control'
                        placeholder='Landline'
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <div class='col-md-offset-3 col-md-9'>
                      <div class='btn-toolbar'>
                        <AsyncButton
                          class='btn btn-primary pull-right'
                          text='Save & Next'
                          pendingText='Saving...'
                          onClick={handleSubmit(this.save)}
                        />

                        <AsyncButton
                          class='btn btn-default pull-right'
                          text='Save'
                          pendingText='Saving...'
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
