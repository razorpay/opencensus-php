import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import Alert from 'rzp/ui/Forms/Alert';
import ModalHeader from 'rzp/ui/ModalHeader';
import { required, length } from 'rzp/utils/validators';
import { saveGST } from 'merchant/modules/profile';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';

const selector = formValueSelector('newGST');

@connect(
  state => {
    return {
      selectedGSTType: selector(state, 'gst_type'),
    };
  },
  {
    saveGST,
    ...ModalActions,
    ...NotificationsActions,
  }
)
@reduxForm({
  form: 'newGST',
})
export default class AddGST extends Component {
  state = {
    label: 'Provisional GST Number',
    fieldName: 'p_gstin',
  };

  componentWillMount() {
    let initialValues = {
      gst_type: 'p_gstin',
    };

    this.props.initialize({
      ...initialValues,
      ...this.props.merchant_gst,
    });
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.selectedGSTType === 'gstin') {
      this.setState({
        label: 'GST Number',
        fieldName: 'gstin',
      });
    } else {
      this.setState({
        label: 'Provisional GST Number',
        fieldName: 'p_gstin',
      });
    }
  }

  save = ({ gst_type, ...fieldProps }) => {
    if (gst_type === 'gstin') {
      fieldProps = {
        gstin: fieldProps.gstin,
      };
    }

    return this.props
      .saveGST(fieldProps)
      .then(item => {
        this.props.showNotification({
          type: 'success',
          message: 'GST saved successfully',
        });
        this.props.closeModal();
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit, merchant_gst, selectedGSTType } = this.props;

    return (
      <div>
        <ModalHeader
          title={merchant_gst ? 'Edit GST Details' : 'Add your GST Details'}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <form onSubmit={handleSubmit(this.save)}>
            <div class="help-block">
              Entered GST number will appear on invoices that we send to you
            </div>

            <ul class="block-radio-group list-group">
              <li class="list-group-item">
                <Field
                  name="gst_type"
                  component="input"
                  type="radio"
                  id="p_gstin"
                  value="p_gstin"
                />
                <label for="p_gstin">
                  Provisional GST Number
                  <i class="pull-right fa fa-check" />
                </label>
              </li>
              <li class="list-group-item">
                <Field
                  name="gst_type"
                  component="input"
                  type="radio"
                  id="gstin"
                  value="gstin"
                />
                <label for="gstin">
                  GST Number
                  <i class="pull-right fa fa-check" />
                </label>
              </li>
            </ul>

            <div class="form-group">
              <label class="label-required">{this.state.label}</label>
              <div>
                <Field
                  name={this.state.fieldName}
                  component={InputField}
                  class="form-control"
                  autoFocus={true}
                  placeholder="19AAAAAA1234YYY"
                  validate={[required(), length(15)]}
                />
              </div>
            </div>

            <div class="help-block">
              {selectedGSTType === 'p_gstin'
                ? 'After submitting the Provisional GST, you can submit the action GST later.'
                : 'Actual GST once submitted cannot be updated via dashboard'}

            </div>

            <div class="Modal__actions">
              <AsyncButton
                type="submit"
                class="btn btn-primary btn-block"
                text="Save my GST Details"
                pendingText="Saving..."
                onClick={handleSubmit(this.save)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}
