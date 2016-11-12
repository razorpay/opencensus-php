import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import ModalHeader from 'rzp/ui/ModalHeader'
import { isBlank } from 'rzp/utils/rzp-utils'
import * as PlanActions from 'merchant/modules/plans'

const validate = values => {
  const errors = {}
  if (isBlank(values.name)) {
    errors.name = 'Required'
  }

  if (isBlank(values.amount)) {
    errors.amount = 'Required'
  }
  return errors
}

const selector = formValueSelector('newPlan')
@connect(
  (state) => {
    let isNew = !selector(state, 'id')
    return {
      isNew
    }
  },
  PlanActions
)
@reduxForm({
  form: 'newPlan',
  validate,
  initialValues: {
    interval_count: 1,
    interval: 'monthly'
  }
})
export default class AddPlan extends Component {
  constructor() {
    super(...arguments)
    this.create = ::this.create
    this.edit = ::this.edit
  }

  create(fieldProps) {
    return this.props.createPlan(fieldProps).then((response) => {
      let plan = response.data.plan
      this.props.planAdded(plan)
      this.props.onSave(plan)
    })
  }

  edit(fieldProps) {
    let { id, ...params } = fieldProps
    return this.props.editPlan(id, params).then((response) => {
      let plan = response.data.plan
      this.props.planEdited(plan)
      this.props.onSave(plan)
    })
  }

  render() {
    const { handleSubmit, isNew } = this.props
    let action = isNew ? this.create : this.edit

    return (
      <div>
        <ModalHeader
          title={isNew ? 'New Plan' : 'Edit Plan'}
          onCloseClick={this.props.closeModal}
        />

        <form class='form-horizontal'>
          <div class='modal-body'>
            <div class='form-group'>
              <label class='col-md-3 control-label'>Name</label>
              <div class='col-md-9'>
                <Field
                  name='name'
                  component={InputField}
                  class='form-control'
                  autoFocus={true}
                />
              </div>
            </div>

            <div class='form-group'>
              <label class='col-md-3 control-label'>Amount</label>
              <div class='col-md-9'>
                <div class='input-group'>
                  <span class='input-group-addon'>INR</span>
                  <Field
                    name='amount'
                    component={InputField}
                    class='form-control'
                  />
                </div>
              </div>
            </div>

            <div class='form-group'>
              <label class='col-md-3 control-label'>Billing Frequency</label>
              <div class='col-md-2'>
                <Field
                  name='interval_count'
                  component={InputField}
                  class='form-control'
                  type='tel'
                />
              </div>

              <div class='col-md-7'>
                <Field
                  name='interval'
                  component='select'
                  class='form-control'
                >
                  <option value='weekly'>Weekly</option>
                  <option value='monthly'>Monthly</option>
                  <option value='yearly'>Yearly</option>
                </Field>
              </div>
            </div>
          </div>

          <div class='modal-footer'>
            <button
              type='button'
              class='btn btn-default'
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type='button'
              class='btn btn-primary'
              text='Save'
              onClick={handleSubmit(action)}
            />
          </div>
        </form>
      </div>
    )
  }
}
