import { connect } from 'react-redux';

import ModalHeader from 'common/ui/ModalHeader';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import Alert from 'common/ui/Forms/Alert';
import { compose } from 'redux';
import React from 'react';

class SendLinkModal extends React.PureComponent {
  _isMounted = true;

  constructor(props) {
    super();

    this.state = {
      disableSendLink: false,
    };
  }

  onSubmit = (body) => {
    this.setState({ disableSendLink: true });

    return this.props
      .onSubmit(body)
      .then((resp) => {
        if (this._isMounted) {
          this.setState({
            disableSendLink: false,
          });
        }

        return resp;
      })
      .catch((resp) => {
        if (this._isMounted) {
          this.setState({
            disableSendLink: false,
          });
        }

        return resp;
      });
  };

  componentWillUnmount() {
    this._isMounted = false;
  }

  render() {
    const { isTestMode, email, sms, description, children, testModeMessage, closeModal } =
        this.props,
      { disableSendLink } = this.state;

    return (
      <div className="SendLink--Modal">
        <ModalHeader title="Send Link" onCloseClick={closeModal} />

        <div className="modal-body">
          {description && <p>{description}</p>}

          <Form className="full-span" onSubmit={this.onSubmit}>
            {email && <Input.Check name="email" defaultValue={'1'} fieldLabel={email} />}

            {sms && <Input.Check name="sms" defaultValue={'1'} fieldLabel={sms} />}

            {children}

            {isTestMode && (
              <Alert
                className="alert-sm"
                type="warning"
                message={testModeMessage}
                showDismiss={false}
              />
            )}

            <Button.Primary type="submit" className="btn-block" disabled={disableSendLink}>
              Send Link
            </Button.Primary>
          </Form>
        </div>
      </div>
    );
  }
}

export default compose(
  connect((state) => ({
    isTestMode: state.session.mode === 'test',
  })),
)(SendLinkModal);
