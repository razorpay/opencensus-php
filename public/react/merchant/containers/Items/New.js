import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
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
  validate: validator({
    name: {
      presence: true
    },
    rate: {
      presence: true
    }
  })
})
export default class AddItem extends Component {
  constructor() {
    super(...arguments)
    this.create = ::this.create
    this.edit = ::this.edit
  }

  create(fieldProps) {
    return this.props.createItem(fieldProps).then((response) => {
      let item = response.data.item
      this.props.itemAdded(item)
      this.props.onSave(item)
    })
  }

  edit(fieldProps) {
    let { id, ...params } = fieldProps
    return this.props.editItem(id, params).then((response) => {
      let item = response.data.item
      this.props.itemEdited(item)
      this.props.onSave(item)
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
                    name='rate'
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
