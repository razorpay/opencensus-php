import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import Alert from 'rzp/ui/Forms/Alert'
import ModalHeader from 'rzp/ui/ModalHeader'
import validator from 'rzp/utils/validator'
import * as ItemActions from 'merchant/modules/items'

const selector = formValueSelector('newItem')
@connect(
  (state) => {
    let isNew = !selector(state, 'id')
    return {
      isNew
    }
  },
  ItemActions
)
@reduxForm({
  form: 'newItem',
  initialValues: {
    currency: 'INR'
  },
  validate: validator({
    name: {
      presence: true
    },
    amount: {
      presence: true
    }
  })
})
export default class AddItem extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      errors: null
    }

    this.create = ::this.create
    this.edit = ::this.edit
  }

  create(fieldProps) {
    let { amount, ...itemParams } = fieldProps
    itemParams.amount = amount*100

    return this.props.createItem(itemParams).then((response) => {
      let item = response.data
      this.props.onSave(item)
    }).catch((err) => {
      this.setState({
        errors: err.errors
      })
    })
  }

  edit(fieldProps) {
    let { id, ...params } = fieldProps
    return this.props.editItem(id, params).then((response) => {
      let item = response.data
      this.props.onSave(item)
    }).catch((err) => {
      this.setState({
        errors: err.errors
      })
    })
  }

  render() {
    const { handleSubmit, isNew } = this.props
    let action = isNew ? this.create : this.edit

    return (
      <div>
        <ModalHeader
          title={isNew ? 'New Item' : 'Edit Item'}
          onCloseClick={this.props.closeModal}
        />

        <Alert
          type='error'
          message={this.state.errors}
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
              <label class='col-md-3 control-label'>Rate</label>
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
              <label class='col-md-3 control-label'>Description</label>
              <div class='col-md-9'>
                <Field
                  name='description'
                  component='textarea'
                  class='form-control'
                />
              </div>
            </div>
          </div>

          <div class='modal-footer'>
            <button
              type='button'
              class='btn btn-default btn-rounded'
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type='button'
              class='btn btn-primary btn-rounded'
              text='Save'
              pendingText='Saving...'
              onClick={handleSubmit(action)}
            />
          </div>
        </form>
      </div>
    )
  }
}
