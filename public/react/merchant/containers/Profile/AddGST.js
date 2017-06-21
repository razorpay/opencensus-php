import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import Alert from 'rzp/ui/Forms/Alert';
import ModalHeader from 'rzp/ui/ModalHeader';
import { saveGST } from 'merchant/modules/profile';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';

// Conditional field-level validation has some bug https://github.com/erikras/redux-form/issues/3012.
// So using the form-level validation
function validate(values) {
  const errors = {};
  const errorMsg = 'Must be 15 characters';

  let { gst_type, p_gstin, gstin } = values;

  if (gst_type === 'p_gstin') {
    if (!p_gstin || p_gstin.length !== 15) {
      errors.p_gstin = errorMsg;
    }
  } else if (gst_type === 'gstin') {
    if (!gstin || gstin.length !== 15) {
      errors.gstin = errorMsg;
    }
  }

  return errors;
}

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
  validate,
})
export default class AddGST extends Component {
  state = {};

  componentWillMount() {
    let initialValues = {
      gst_type: 'p_gstin',
    };

    this.props.initialize({
      ...initialValues,
      ...this.props.merchant_gst,
    });
  }

  save = ({ gst_type, ...otherProps }) => {
    let fieldProps = {};
    fieldProps[gst_type] = otherProps[gst_type];

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
    let isNew = !merchant_gst.p_gstin && !merchant_gst.gstin;
    let isPGST = selectedGSTType === 'p_gstin';

    return (
      <div>
        <ModalHeader
          title={isNew ? 'Add your GST Details' : 'Edit GST Details'}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <form onSubmit={handleSubmit(this.save)}>
            <div class="help-block">
              Entered GSTIN will appear on invoices that we send to you
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
                  Provisional GSTIN
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
                  GSTIN
                  <i class="pull-right fa fa-check" />
                </label>
              </li>
            </ul>

            {isPGST
              ? <div class="form-group">
                  <label class="label-required">Provisional GSTIN</label>
                  <div>
                    <Field
                      name="p_gstin"
                      component={InputField}
                      class="form-control"
                      autoFocus={true}
                      placeholder="19AAAAAA1234YYY"
                    />
                  </div>
                </div>
              : <div class="form-group">
                  <label class="label-required">GSTIN</label>
                  <div>
                    <Field
                      name="gstin"
                      component={InputField}
                      class="form-control"
                      autoFocus={true}
                      placeholder="19AAAAAA1234YYY"
                    />
                  </div>
                </div>}

            <div class="help-block">
              {isPGST
                ? 'You can submit your final GSTIN here once you have received it.'
                : 'final GSTIN once submitted cannot be updated via dashboard.'}

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
