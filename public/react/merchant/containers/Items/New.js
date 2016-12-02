import { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm, formValueSelector } from 'redux-form'
import AsyncButton from 'react-async-button'
import InputField from 'rzp/ui/Forms/InputField'
import Alert from 'rzp/ui/Forms/Alert'
import ModalHeader from 'rzp/ui/ModalHeader'
import validator from 'rzp/utils/validator'
import * as ItemActions from 'merchant/modules/items'

@connect(
  null,
  ItemActions
)
@reduxForm({
  form: 'newItem',
  validate: validator({
    name: {
      presence: true
    },
    amountInINR: {
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

    this.save = ::this.save
  }

  componentWillMount() {
    if (this.props.item) {
      this.props.initialize(this.props.item)
    }
  }

  save(props) {
    return this.props.saveItem(props).then((item) => {
      this.props.onSave(item)
    }).catch((err) => {
      this.setState({
        errors: err.errors
      })
    })
  }

  render() {
    const { handleSubmit } = this.props

    return (
      <div>
        <ModalHeader
          title={this.props.item ? 'Edit Item' : 'New Item'}
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
                    name='amountInINR'
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
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    )
  }
}

AddItem.defaultProps = {
  onSave: () => {}
}
