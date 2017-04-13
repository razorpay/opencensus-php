import { Component } from 'react'
import { Field } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import { required, email, phone } from 'rzp/utils/validators'
import ActivationWizardHOC from './ActivationWizardHOC'

@ActivationWizardHOC
export default class ContactDetailsForm extends Component {
  render() {
    let {
      handleSubmit,
      save,
      saveAndNext,
    } = this.props

    return (
      <form class='form-horizontal' onSubmit={handleSubmit(saveAndNext)}>
        <fieldset disabled={this.props.data.locked}>
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
                  onClick={handleSubmit(saveAndNext)}
                />

                <AsyncButton
                  class='btn btn-default pull-right'
                  text='Save'
                  pendingText='Saving...'
                  onClick={handleSubmit(save)}
                />
              </div>
            </div>
          </div>
        </fieldset>
      </form>
    )
  }
}
