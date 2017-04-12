import { Component } from 'react'
import { Field } from 'redux-form'
import AsyncButton from 'react-async-button'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
import ActivationBaseHOC from './ActivationBase'

@ActivationBaseHOC
export default class BusinessDetailsForm extends Component {
  render() {
    let {
      handleSubmit,
      save,
      saveAndNext,
      gotoTab,
    } = this.props

    return (
      <form class='form-horizontal' onSubmit={handleSubmit(saveAndNext)}>
        <fieldset>
          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Organisation Type</label>
            <div class='col-md-9'>
              <Field
                name='business_type'
                component='select'
                class='form-control'
                autoFocus={true}
              >
                <option></option>
                <option value='1'>Proprietorship</option>
                <option value='2'>Individual</option>
                <option value='3'>Partnership</option>
                <option value='4'>Private Limited</option>
                <option value='5'>Public Limited</option>
                <option value='6'>LLP</option>
                <option value='7'>NGO</option>
                <option value='8'>Educational Institutes</option>
                <option value='9'>Trust</option>
                <option value='10'>Society</option>
              </Field>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Full Business Name</label>
            <div class='col-md-9'>
              <Field
                name='business_name'
                component='input'
                class='form-control'
                placeholder='Acme Private Limited'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Billing Label</label>
            <div class='col-md-9'>
              <Field
                name='business_dba'
                component='input'
                class='form-control'
                placeholder='Acme Watches'
              />
              <small class='help-block'>This is the brand name that the customers are familiar with.</small>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>International Payments Required?</label>
            <div class='col-md-9 checkbox'>
              <label class='i-switch'>
                <Field
                  name='business_international'
                  component='input'
                  type='checkbox'
                />
                <i></i>
              </label>
              <small class='help-block'>Please note that applications for international transactions take longer time to process.</small>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label'>Payments Accepted for:</label>
            <div class='col-md-9'>
              <Field
                name='business_paymentdetails'
                component='select'
                class='form-control'
              >
                <option></option>
                <option value='B2B'>Business to Business (B2B)</option>
                <option value='B2C'>Business to Consumer (B2C)</option>
                <option value='B2B+B2C'>Both B2B and B2C</option>
              </Field>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Business Model</label>
            <div class='col-md-9'>
              <Field
                name='business_model'
                component='textarea'
                class='form-control'
                placeholder='Business Model'
                maxLength={255}
              />
              <small class='help-block'>Please give a brief explanation of your business model and future plans (Essential for startups)</small>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Registered Address</label>
            <div class='col-md-9'>
              <Field
                name='business_registered_address'
                component='textarea'
                class='form-control'
                placeholder='Registered Address'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Registration Address State</label>
            <div class='col-md-9'>
              <Field
                name='business_registered_state'
                component='input'
                class='form-control'
                placeholder='Registered Address State'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Registered Address City</label>
            <div class='col-md-9'>
              <Field
                name='business_registered_city'
                component='input'
                class='form-control'
                placeholder='Registered Address City'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Registered Address Pincode</label>
            <div class='col-md-9'>
              <Field
                name='business_registered_pin'
                component='input'
                class='form-control'
                placeholder='Registered Address Pincode'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label'>Operational Address same as Registered Address</label>
            <div class='col-md-9 checkbox'>
              <label class='i-switch'>
                <Field
                  name='or_same'
                  component='input'
                  type='checkbox'
                />
                <i></i>
              </label>
              <small class='help-block'>Physical Verification might be performed at your operational address.</small>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Operational Address</label>
            <div class='col-md-9'>
              <Field
                name='business_operation_address'
                component='textarea'
                class='form-control'
                placeholder='Operational Address'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Operational Address State</label>
            <div class='col-md-9'>
              <Field
                name='business_operation_state'
                component='input'
                class='form-control'
                placeholder='Operational Address State'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Operational Address City</label>
            <div class='col-md-9'>
              <Field
                name='business_operation_city'
                component='input'
                class='form-control'
                placeholder='Operational Address City'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Operational Address Pincode</label>
            <div class='col-md-9'>
              <Field
                name='business_operation_pin'
                component='input'
                class='form-control'
                placeholder='Operational Address Pincode'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Date of Establishment</label>
            <div class='col-md-9'>
              <Field
                name='business_doe'
                component={DatePickerField}
                class='form-control'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Company CIN</label>
            <div class='col-md-9'>
              <Field
                name='company_cin'
                component='input'
                class='form-control'
                placeholder='Company CIN'
              />
              <small class='help-block'>Mandatory for Companies</small>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Company PAN</label>
            <div class='col-md-9'>
              <Field
                name='company_pan'
                component='input'
                class='form-control'
                placeholder='Company PAN'
              />
              <small class='help-block'>Mandatory for Companies</small>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Name on PAN Card</label>
            <div class='col-md-9'>
              <Field
                name='company_pan_name'
                component='input'
                class='form-control'
                placeholder='Name on PAN (provided above)'
              />
              <small class='help-block'>Mandatory for Companies</small>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Expected annual transaction volume (INR)</label>
            <div class='col-md-9'>
              <Field
                name='transaction_volume'
                component='select'
                class='form-control'
              >
                <option></option>
                <option value='1'>&lt; 1 Lakh</option>
                <option value='2'>1 to 10 lakh</option>
                <option value='3'>10 Lakh to 1 Crore</option>
                <option value='4'>&gt; 1 Crore</option>
              </Field>
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label'>Expected average transaction value</label>
            <div class='col-md-9'>
              <Field
                name='transaction_value'
                component='input'
                class='form-control'
                placeholder='E.g. Average price of the commodities you sell'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>PAN of any 1 authorised signatory/promoter/director</label>
            <div class='col-md-9'>
              <Field
                name='promoter_pan'
                component='input'
                class='form-control'
                placeholder='PAN Number of Promoter'
              />
            </div>
          </div>

          <div class='form-group'>
            <label class='col-md-3 control-label label-required'>Name on PAN Card</label>
            <div class='col-md-9'>
              <Field
                name='promoter_pan_name'
                component='input'
                class='form-control'
                placeholder='Name on PAN Card'
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
                  onClick={() => gotoTab(1)}
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
