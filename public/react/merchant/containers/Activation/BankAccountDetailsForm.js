import { Component } from 'react'
import { Field } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import { required } from 'rzp/utils/validators'
import { states } from 'rzp/utils/constants'
import ActivationWizardHOC from './ActivationWizardHOC'

function verifyAccountNumber(value, allValues, props) {
  return value !== allValues.bank_account_number ? 'Bank Number doesn\'t match' : undefined
}

@ActivationWizardHOC
export default class BankDetailsForm extends Component {
  render() {
    let {
      handleSubmit,
      save,
      saveAndNext,
      gotoTab,
    } = this.props

    return (
      <form class='form-horizontal' onSubmit={handleSubmit(save)}>
        <fieldset>
          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Branch IFSC Code</label>
            <div class='col-md-9'>
              <Field
                name='bank_branch_ifsc'
                component={InputField}
                class='form-control'
                placeholder='IFSC Code of the Bank Branch'
                autoFocus={true}
                validate={[
                  required()
                ]}
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Bank Account Number</label>
            <div class='col-md-9'>
              <Field
                name='bank_account_number'
                component={InputField}
                class='form-control'
                placeholder='Bank Account Number'
                type='password'
                autoComplete='off'
                validate={[
                  required()
                ]}
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Re-enter Bank Account Number</label>
            <div class='col-md-9'>
              <Field
                name='bank_account_number_confirmation'
                component={InputField}
                class='form-control'
                placeholder='Re-enter your Bank Account Number'
                validate={[
                  required(),
                  verifyAccountNumber,
                ]}
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Bank Account Type</label>
            <div class='col-md-9'>
              <Field
                name='bank_account_type'
                component={InputField}
                class='form-control'
                placeholder='Bank Account Type e.g. Current'
                validate={[
                  required()
                ]}
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Beneficiary Name</label>
            <div class='col-md-9'>
              <Field
                name='bank_account_name'
                component={InputField}
                class='form-control'
                placeholder='Account Holder Name'
                validate={[
                  required()
                ]}
              />
              <small class='help-block'>Should be same as business/individual name</small>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Beneficiary Address Line 1</label>
            <div class='col-md-9'>
              <Field
                name='bank_beneficiary_address1'
                component={InputField}
                tagName='textarea'
                class='form-control'
                placeholder='Beneficiary Address Line 1'
                validate={[
                  required()
                ]}
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label'>Beneficiary Address Line 2</label>
            <div class='col-md-9'>
              <Field
                name='bank_beneficiary_address2'
                component={InputField}
                tagName='textarea'
                class='form-control'
                placeholder='Beneficiary Address Line 2'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label'>Beneficiary Address Line 3</label>
            <div class='col-md-9'>
              <Field
                name='bank_beneficiary_address3'
                component={InputField}
                tagName='textarea'
                class='form-control'
                placeholder='Beneficiary Address Line 3'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Beneficiary Address City</label>
            <div class='col-md-9'>
              <Field
                name='bank_beneficiary_city'
                component={InputField}
                class='form-control'
                placeholder='Beneficiary Address City'
                validate={[
                  required()
                ]}
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Beneficiary Address State</label>
            <div class='col-md-9'>
              <Field
                name='bank_beneficiary_state'
                component={InputField}
                tagName='select'
                class='form-control'
                placeholder='Beneficiary Address State'
                validate={[
                  required()
                ]}
              >
                <option></option>
                {
                  Object.keys(states).map((stateCode) => (
                    <option value={stateCode} key={stateCode}>{states[stateCode]}</option>
                  ))
                }
              </Field>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Beneficiary Address Pincode</label>
            <div class='col-md-9'>
              <Field
                name='bank_beneficiary_pin'
                component={InputField}
                class='form-control'
                placeholder='Beneficiary Address Pincode'
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
                  onClick={() => gotoTab(2)}
                />

                <AsyncButton
                  class='btn btn-primary pull-right'
                  text='Save & Next'
                  pendingText='Saving...'
                  onClick={handleSubmit(saveAndNext)}
                />

                <AsyncButton
                  type='button'
                  class='btn btn-default pull-right'
                  text='Save'
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
