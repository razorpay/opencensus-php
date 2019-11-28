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
            <p>{description1}</p>

            <Input.Check
              name="email"
              class="InputGroup--vTop"
              defaultValue={'1'}
              fieldLabel={email}
            />

            <Input.Check
              name="phone"
              class="InputGroup--vTop"
              defaultValue={'1'}
              fieldLabel={phone}
            />

            <p>{description2}</p>

            {isTestMode && <Alert type="warning" message={testModeMessage} />}

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
