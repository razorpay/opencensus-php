import { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm } from 'redux-form';
import Alert from 'rzp/ui/Forms/Alert';
import { without } from 'rzp/utils/rzp-utils';
import { showNotification } from 'rzp/modules/notifications';
import * as ActivationActions from 'merchant/modules/activation';
import { fetchUser } from 'merchant/modules/session';

import ContactDetailsForm from './ContactDetailsForm';
import BusinessDetailsForm from './BusinessDetailsForm';
import WebsiteDetailsForm from './WebsiteDetailsForm';
import BankAccountDetailsForm from './BankAccountDetailsForm';
import DocumentsUploadForm from './DocumentsUploadForm';
import SubmitForm from './SubmitForm';

const FORM_COMPONENTS = {
  activationContactDetails: ContactDetailsForm,
  activationBusinessDetails: BusinessDetailsForm,
  activationWebsiteDetails: WebsiteDetailsForm,
  activationBankAccounts: BankAccountDetailsForm,
  activationDocumentUpload: DocumentsUploadForm,
  activationSubmitForm: SubmitForm,
};

// ====
// Heimdall specific
// ====
@connect(
  state => {
    let session = state.session;
    let { data, ...otherProps } = state.activation;
    let initialValues = { ...data };

    if (session.org.custom_code === 'hdfc') {
      initialValues.bank_account_type =
        initialValues.bank_account_type || 'Current';
    }

    return {
      initialValues: {
        ...initialValues,
      },
      session: state.session,
      ...state.activation,
    };
  },
  { ...ActivationActions, showNotification, fetchUser }
)
@reduxForm({
  form: 'activationForm',
  destroyOnUnmount: false,
  enableReinitialize: true,
  // keepDirtyOnReinitialize: true,
})
export default class WizardItem extends Component {
  finalStep = 6;
  state = {
    errors: null,
  };

  componentWillMount() {
    if (this.props.accountId) {
      if (this.props.linkedAccountKyc === 0) {
        this.finalStep = 3;
      } else {
        this.finalStep = 4;
      }
    }
  }

  _save = props => {
    let { step, accountId } = this.props;
    let data = without(props, [
      'or_same',
      'bank_account_number_confirmation',
      'steps_finished',
    ]);

    if (step === this.finalStep) {
      return this.props.submitForm({ step, data, accountId }).then(() => {
        return this.props.fetchActivationDetails(accountId);
      });
    } else {
      return this.props.saveStep({ step, data, accountId });
    }
  };

  save = props => {
    return this._save(props)
      .then(response => {
        let step = this.props.step;

        // For updating the accounts list view on success of activation
        if (step === this.finalStep && this.props.callback) {
          this.props.callback();
        }

        let message =
          step === this.finalStep
            ? 'Form submitted Successfully!'
            : 'Step saved successfully';

        this.setState({
          errors: null,
        });

        this.props.initialize(props);

        this.props.showNotification({
          type: 'success',
          message,
        });
        this.updateActivationProgress();
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });

        throw { errors: err.errors };
      });
  };

  saveAndNext = props => {
    return this.save(props).then(() => {
      this.goNext();
    });
  };

  saveFile = (event, fieldName) => {
    let files = event.target.files;

    return this.props
      .saveFile({
        fieldName,
        file: files[0],
        step: this.props.step,
        accountId: this.props.accountId,
      })
      .then(response => {
        this.props.showNotification({
          type: 'success',
          message: 'File uploaded successfully',
        });
        this.updateActivationProgress();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  goBack = () => {
    this.props.gotoTab(this.props.step - 1);
  };

  goNext = () => {
    this.props.gotoTab(this.props.step + 1);
  };

  updateActivationProgress() {
    return this.props.fetchUser().then(response => {
      // TODO: Remove this manual updation of activation progress once the nav is migrated to react
      $('#activationNav b').text(`${response.data.activation_progress}%`);
    });
  }

  render() {
    let WizardForm = FORM_COMPONENTS[this.props.form];

    return (
      <div class="panel">
        <div class="panel-body">
          <div class="row">
            <div
              class={`${this.props.accountId
                ? ''
                : 'col-lg-10'} col-md-12 col-sm-12`}
            >
              <div class="row">
                <div class="col-md-offset-3 col-md-9">
                  <h4 class="wizard-header">{this.props.pageTitle}</h4>
                  <Alert type="error" message={this.state.errors} />
                </div>
              </div>

              <WizardForm
                {...this.props}
                goBack={this.goBack}
                goNext={this.goNext}
                save={this.save}
                saveAndNext={this.saveAndNext}
                saveFile={this.saveFile}
              />
            </div>
          </div>
        </div>
      </div>
    );
  }
}
