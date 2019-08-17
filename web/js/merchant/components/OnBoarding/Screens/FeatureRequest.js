import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';
import LocalStorageService from 'rzp/utils/localStorage';

import Image from 'rzp/ui/Image';
import Spinner from 'rzp/ui/Spinner';

import Button, { AsyncBtn } from 'component/Button';

import { showNotification } from 'rzp/modules/notifications';

import {
  saveOnboarding,
  getOnboardingResponse,
} from 'merchant/modules/onboarding';

import OnBoardingForm from '../OnBoardingForm';
import { isFormValid } from '../Forms';

@connect(
  state => ({
    user: state.session.user,
  }),
  {
    saveOnboarding,
    getOnboardingResponse,
    showNotification,
  }
)
export default class OnBoardingFeatureRequest extends React.PureComponent {
  constructor(props) {
    super(props);

    this.state = {
      submitted: false,
      isLoading: false,
      uploadedFile: null,
      form: {},
    };
  }

  componentWillMount() {
    this.setState({
      isLoading: true,
    });

    this.props
      .getOnboardingResponse(this.props.formType)
      .then(response => {
        const submitted = !!response.data.id;

        this.setState({
          isLoading: false,
          submitted: submitted,
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  }

  handleChange = e => {
    this.setState({
      form: {
        ...this.state.form,
        [e.target.name]: e.target.value,
      },
    });
  };

  handleFileUpload = file => {
    this.setState({
      form: {
        ...this.state.form,
        uploadedFile: file,
      },
    });

    return Promise.resolve();
  };

  // Form submit handler
  onSubmitClick = () => {
    let props = { ...this.state.form };

    let file = null;
    let fileName = null;

    if (props.uploadedFile && this.props.formType === RZPFeatures.ROUTE) {
      file = props.uploadedFile;
      fileName = 'vendor_agreement';
    }

    return this.props
      .saveOnboarding(this.props.formType, props, file, fileName)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Your request has been submitted',
        });

        this.setState({ submitted: true });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  switchToTestMode = () => {
    const { user } = this.props;

    LocalStorageService.setItem(`rzp_mode--${user.current}`, 'test');
    window.location.reload();
  };

  render() {
    const { prev, desc, imageUrl, formType, user, heading } = this.props,
      { submitted, isLoading } = this.state;

    const disabled = isFormValid(formType, this.state.form);

    return (
      <div class="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--FeatureRequest">
        <div class="Landing--Image">
          <Image src={imageUrl} />
        </div>

        <div class="FeatureRequest">
          <div>
            <span class="Dash" /> Get Started
          </div>

          <p class="desc">{desc}</p>

          <FeatureRequestForm
            user={user}
            heading={heading}
            isLoading={isLoading}
            submitted={submitted}
            formType={formType}
            handleChange={this.handleChange}
            handleFileUpload={this.handleFileUpload}
            switchToTestMode={this.switchToTestMode}
          />

          <div class="Button-Container">
            <Button.Transparent iconBefore="arrow-back" onClick={prev}>
              Back
            </Button.Transparent>

            {!submitted &&
              !isLoading && (
                <AsyncBtn.Primary
                  class="Forward-Button"
                  onClick={this.onSubmitClick}
                  disabled={disabled}
                >
                  Submit
                </AsyncBtn.Primary>
              )}
          </div>
        </div>
      </div>
    );
  }
}

const FeatureRequestForm = ({
  isLoading,
  formType,
  handleChange,
  handleFileUpload,
  switchToTestMode,
  heading,
  submitted,
  user,
}) => {
  if (isLoading) {
    return (
      <div class="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  if (!submitted) {
    return (
      <div class="FeatureRequest--Form">
        <OnBoardingForm
          formType={formType}
          handleChange={handleChange}
          handleFileUpload={handleFileUpload}
        />
      </div>
    );
  }

  return (
    <div class="alert alert-info">
      Your form is submitted for enabling {heading}. Meanwhile, you can switch
      to <a onClick={switchToTestMode}>Test Mode</a> to try the product.
      {!user.isActivated ? (
        <div class="m-t">
          <b> Please note </b> that this is activation form for {heading}. Your
          request will be processed after you submit the{' '}
          <Link to="/activation">primary activation form</Link>.
        </div>
      ) : (
        <div class="m-t">
          <b>Please note </b> that your application is under review. We will
          reach out on your contact email for all updates.
        </div>
      )}
    </div>
  );
};
