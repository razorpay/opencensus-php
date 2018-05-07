import { connect } from 'react-redux';
import { merchantFetch } from 'rzp/utils/ajax';
import { showNotification } from 'rzp/modules/notifications';
import { without } from 'rzp/utils/rzp-utils';
import Spinner from 'rzp/ui/Spinner';
import { Modal, ModalContent } from 'component/Modal';
import { LinkCard } from 'component/Cards';
import ActivationWizard from 'component/merchant/Activation';
import Button from 'component/Button';

import { updateSession } from 'merchant/modules/session';
import User from 'merchant/models/User';

import { withRouter } from 'react-router-dom';

@withRouter
@connect(
  state => ({
    session: state.session,
    user: state.session.user,
  }),
  {
    showNotification,
    updateSession,
  }
)
export default class ActivationContainer extends React.Component {
  state = {
    data: null,
    categories: null,
  };

  componentWillMount() {
    this.fetchActivationDetails();
  }

  fetchActivationDetails() {
    Promise.all([
      merchantFetch({
        url: 'merchant/activation',
        mode: 'live',
      }),
      merchantFetch('merchant/activation/business_categories'),
    ]).then(([data, categories]) => {
      this.setState({
        data: data.data,
        categories: categories.data,
        isFormTouched: isFormTouched(data.data),
      });
    });
  }

  updateSession(data) {
    const { session } = this.props;

    const { activation_progress, activated, submitted } = data;

    const user = new User({
      ...session.user,
      activation_progress,
      activated,
      submitted: +submitted,
    });

    this.props.updateSession({
      user,
      mode: session.mode,
    });
  }

  submitForm = (data, accountId) => {
    return merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      method: 'post',
      data: { submit: 1 },
    })
      .then(response => {
        if (!response.data.can_submit) {
          throw { errors: ['Some mandatory fields are required'] };
        }

        this.updateSession(response.data);
        return response;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        throw err;
      });
  };

  saveStep = (data, accountId) => {
    return merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      method: 'post',
      data,
    })
      .then(response => {
        console.log('....', response.data);
        this.updateSession(response.data);

        return response;
      })
      .catch(err => {});
  };

  // TODO: Figure out accountId from props
  saveFile = (fieldName, file, progressTracker, accountId) => {
    let formData = new FormData();

    let fieldNameMapping = {
      business_proof_url: 'business_proof_url',
      business_operation_proof: 'business_operation_proof_url',
      business_pan_url: 'business_pan_url',
      address_proof_url: 'address_proof_url',
      promoter_proof: 'promoter_proof_url',
      promoter_pan_proof: 'promoter_pan_url',
      promoter_address_url: 'promoter_address_url',
      ngo_12a_proof: 'form_12a_url',
      ngo_80g_proof: 'form_80g_url',
    };
    formData.append(fieldNameMapping[fieldName], file);

    // TODO: Temporary notification in then-catch, success-error msg would be adjusted in custom UI for file upload.
    return merchantFetch({
      url: 'merchant/activation/upload',
      method: 'post',
      mode: 'live',
      data: formData,
      accountId,
      onUploadProgress: progressTracker,
    })
      .then(response => {
        if (response.data) {
          this.props.showNotification({
            type: 'success',
            message: 'File uploaded successfully',
          });

          this.updateSession(response.data);

          return response;
        }
      })
      .catch(err => {
        console.log('error.', err);
        if (err.errors.length && err.errors[0]) {
          this.props.showNotification({
            type: 'error',
            message: err.errors,
          });
        }
        return err;
      });
  };

  render() {
    let { data, categories } = this.state;

    const filledEvenSingleDetail = false;

    let content, modalClass;

    if (this.props.user.submitted == 1) {
      modalClass = 'Activation--success';
      content = <SuccessScreen />;
    } else if (!data) {
      modalClass = 'Activation--welcome';
      content = (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    } else if (!this.state.isFormTouched && !this.state.openWizard) {
      modalClass = 'Activation--welcome';
      content = (
        <WelcomeScreen
          onClose={this.props.onClose}
          openWizard={() => this.setState({ openWizard: true })}
        />
      );
    } else {
      modalClass = 'Activation--wizard';
      content = (
        <ActivationWizard
          data={data}
          categories={categories}
          save={this.saveStep}
          saveFile={this.saveFile}
          submitForm={this.submitForm}
        />
      );
    }

    return this.props.closeUrl ? (
      <Modal class={'animate-down ' + modalClass} onClose={this.props.onClose}>
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class="ActivationContainer">{content}</div>
    );
  }
}

const SuccessScreen = _ => {
  return (
    <div class="Activation--success">
      <div class="Activation-title">
        <side-title>Activation Form submitted Successfully!</side-title>
        <img src="" />
      </div>
      <div class="Activation-info">
        <i class="i i-check" /> Your form is submitted successfully
        <p class="desc">
          The process usually takes 2 to 3 working days* (which may vary
          depending on our partner bank). We will reach out on your contact
          email for further clarifications.
        </p>
      </div>

      <div class="Activation-actions">
        <side-title>What's Next?</side-title>
        <LinkCard
          title={'Finish Profile Settings'}
          description={
            'Complete your account settings such as theme color, logo, etc.'
          }
          icon={'icon-done'}
          to="/profile"
        />
      </div>
    </div>
  );
};

const WelcomeScreen = ({ onClose, openWizard }) => {
  return (
    <div class="Activation--welcome">
      <div class="short-content">
        <h3> Welcome to Razorpay! Let's get you going.</h3>
        <div class="underline" />
        <p>
          Congrats on signing up with Razorpay. Let's start by filling in your
          basic information such has Business type, Account details, GST info,
          etc.
        </p>
        <p>
          We'll review your details and documents after which you can begin
          accepting payments on your Razorpay dashboard.
        </p>

        {onClose && <Button onClick={onClose}>Activate Later</Button>}
        <Button.Primary onClick={openWizard}>Activate Now</Button.Primary>
      </div>
    </div>
  );
};

ActivationContainer.MODAL_MASK_CLASS = 'Activation';

function isFormTouched(data) {
  if (!data) {
    return false;
  }

  let isDirty = false;

  Object.keys(data).find(key => {
    if (
      defaultKeysInForm.indexOf(key) > -1 ||
      excludedFieldsInForm.indexOf(key) > -1
    ) {
      return false;
    }
    if (data[key] != null) {
      isDirty = true;
      return true;
    }
  });

  return isDirty;
}

const defaultKeysInForm = ['contact_name', 'contact_email', 'contact_mobile'];
const excludedFieldsInForm = [
  'created_at',
  'locked',
  'updated_at',
  'submitted',
  'can_submit',
  'steps_finished',
  'verification',
  'archived',
  'activated',
  'activation_progress',
  'allowed_next_activation_statuses',
];
