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
  render() {
    const {
      isTestMode,
      email,
      phone,
      description1,
      description2,
      testModeMessage,
      closeModal,
    } = this.props;

    return (
      <div class="SendLink--Modal">
        <ModalHeader title="Send Link" onCloseClick={closeModal} />
        <div class="modal-body">
          <Form class="full-span" onSubmit={this.props.onSubmit}>
            {description1 && <p>{description1}</p>}

            <Input.Check name="email" defaultValue={'1'} fieldLabel={email} />

            <Input.Check name="phone" defaultValue={'1'} fieldLabel={phone} />

            {description2 && <p class="m-b">{description2}</p>}

            {isTestMode && (
              <Alert
                class="m-b"
                type="warning"
                message={testModeMessage}
                showDismiss={false}
              />
            )}

            <AsyncBtn.Primary
              type="submit"
              pendingState="Sending..."
              class="btn-block"
              onClick={this.props.onSubmit}
            >
              Send Link
            </AsyncBtn.Primary>
          </Form>
        </div>
      </div>
    );
  }
}
