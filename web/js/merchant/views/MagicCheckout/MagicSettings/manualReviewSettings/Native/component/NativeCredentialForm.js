import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import ModalHeader from 'common/ui/ModalHeader';
import Button from 'common/new-ui/Button';
import InputField from 'merchant/views/MagicCheckout/common/components/InputField';
import { closeModal } from 'merchant_common/reducers/modals';
import { isUrlLenient } from 'common/utils/validators';

const isUrlValid = (value) => {
  if (!isUrlLenient(value)) {
    return 'Please enter a valid URL.';
  }
  return '';
};
const NativeCredentialsForm = ({ closeModal, platform, submitCredentials }) => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [url, setUrl] = useState('');
  const [isCtaEnabled, setIsCtaEnabled] = useState(false);

  const disableCtaClass = !isCtaEnabled ? ' cta-disabled' : '';

  useEffect(() => {
    setIsCtaEnabled(password.length && username.length && url.length);
  }, [username, password, url]);

  const onSubmit = useCallback(() => {
    setIsCtaEnabled(false);
    submitCredentials({
      order_status_update_url: url,
      username,
      password,
    });
  }, [platform, username, password, setIsCtaEnabled, submitCredentials]);

  return (
    <div className="native-credentials-form">
      <ModalHeader
        onCloseClick={closeModal}
        extraClass="form-title"
        title="Review order API & Credentials"
      />
      <div className="form-content">
        <div className="row">
          <InputField
            validator={isUrlValid}
            label="URL to review order API"
            id="api-url"
            value={url}
            setValue={setUrl}
            autoFocus
            placeholder="Enter URL to review order API"
            type="text"
          />
          <InputField
            label="User name"
            id="username"
            value={username}
            setValue={setUsername}
            autoFocus={false}
            placeholder="Enter your username"
            type="text"
          />
          <InputField
            label="Password"
            id="password"
            value={password}
            setValue={setPassword}
            autoFocus={false}
            placeholder="Enter Password"
            type="password"
          />
        </div>
      </div>
      <div className="native-cta-container">
        <Button.Primary
          type="button"
          onClick={onSubmit}
          className={`native-form-cta${disableCtaClass}`}
          disabled={!isCtaEnabled}
        >
          Submit
        </Button.Primary>
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(NativeCredentialsForm);
