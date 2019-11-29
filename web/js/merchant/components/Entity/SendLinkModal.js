import { connect } from 'react-redux';

import { closeModal } from 'merchant_common/reducers/modals';

import ModalHeader from 'common/ui/ModalHeader';
import { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import Alert from 'common/ui/Forms/Alert';

@connect(
  state => ({
    isTestMode: state.session.mode === 'test',
  }),
  {
    closeModal,
  }
)
export default class extends React.PureComponent {
  constructor(props) {
    super(props);

    this.state = {
      _dirty: {
        email: '1',
        sms: '1',
      },
    };
  }

  onChange = e => {
    this.setState({
      _dirty: {
        [e.target.name]: e.target.value,
      },
    });
  };

  onSubmit = () => {
    this.props.onSubmit(this.state._dirty);
  };

  render() {
    const {
      isTestMode,
      email,
      sms,
      description,
      children,
      testModeMessage,
      closeModal,
    } = this.props;

    return (
      <div class="SendLink--Modal">
        <ModalHeader title="Send Link" onCloseClick={closeModal} />

        <div class="modal-body">
          {description && <p>{description}</p>}

          <Form class="full-span" onChange={this.onChange}>
            {email && (
              <Input.Check
                name="email"
                defaultValue={this.state._dirty.email}
                fieldLabel={email}
              />
            )}

            {sms && (
              <Input.Check
                name="sms"
                defaultValue={this.state._dirty.sms}
                fieldLabel={sms}
              />
            )}

            {children}

            {isTestMode && (
              <Alert
                class="alert-sm"
                type="warning"
                message={testModeMessage}
                showDismiss={false}
              />
            )}

            <AsyncBtn.Primary
              type="submit"
              pendingState="Sending..."
              class="btn-block"
              onClick={this.onSubmit}
            >
              Send Link
            </AsyncBtn.Primary>
          </Form>
        </div>
      </div>
    );
  }
}
