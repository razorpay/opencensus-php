import { connect } from 'react-redux';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';

import { closeModal } from 'merchant_common/reducers/modals';

const MAX_ACCOUNTS = 10;
const IFSC_LENGTH = 11;
const ACC_MIN_LENGTH = 5;
const ACC_MAX_LENGTH = 35;

@connect(null, {
  closeModal,
})
export default class ConfigureBankAccounts extends React.Component {
  constructor(props) {
    super(props);

    this.DUMMY_ACCOUNT_DETAILS = {
      id: Date.now(),
      ifsc: '',
      account_number: '',
    };

    this.state = {
      bankAccounts: props.bankAccounts.length
        ? props.bankAccounts
        : [this.DUMMY_ACCOUNT_DETAILS],
    };
  }

  addBankAccount = () => {
    if (this.state.bankAccounts.length >= MAX_ACCOUNTS) {
      return;
    }

    this.setState({
      bankAccounts: [
        ...this.state.bankAccounts,
        {
          ...this.DUMMY_ACCOUNT_DETAILS,
          id: Date.now(),
        },
      ],
    });
  };

  handleSubmit = () => {
    const bankAccounts = this.state.bankAccounts.map(
      ({ account_number, ifsc }) => ({ account_number, ifsc })
    );

    this.props.onSave(bankAccounts);

    this.props.closeModal();
  };

  handleRemoveAccount = id => () => {
    const bankAccounts = this.state.bankAccounts.filter(
      account => account.id != id
    );

    this.setState({
      bankAccounts,
    });
  };

  onChange = id => event => {
    const newBankAccounts = this.state.bankAccounts.map(account => {
      if (account.id === id) {
        const name = event.target.getAttribute('data-name');

        return {
          ...account,
          [name]: event.target.value,
        };
      }

      return account;
    });

    this.setState({
      bankAccounts: newBankAccounts,
    });
  };

  render() {
    const { bankAccounts } = this.state;

    return (
      <div class="PopOver--Modal">
        <div class="title">Authorised Accounts</div>
        <Form onSubmit={this.handleSubmit}>
          {bankAccounts.map(account => (
            <BankAccountDetailsInputForm
              key={account.id}
              {...account}
              onChange={this.onChange(account.id)}
              handleRemoveAccount={this.handleRemoveAccount(account.id)}
            />
          ))}

          <div class="Modal-actions">
            <Button.Transparent
              class="Cancel-btn"
              type="button"
              onClick={this.props.closeModal}
            >
              <span>&times;</span>
              Cancel
            </Button.Transparent>

            <Button.Transparent class="Save-btn" type="submit">
              <span class="icon i-check" />
              Save
            </Button.Transparent>
          </div>
        </Form>

        <div class="actions">
          {bankAccounts.length >= MAX_ACCOUNTS ? (
            'No more accounts can be added'
          ) : (
            <>
              <button
                type="button"
                class="btn-link add-account-btn"
                onClick={this.addBankAccount}
              >
                + Add Another Account{' '}
              </button>

              <span class="message">
                ({MAX_ACCOUNTS - bankAccounts.length} more accounts can be
                added)
              </span>
            </>
          )}
        </div>
      </div>
    );
  }
}

const BankAccountDetailsInputForm = ({
  id,
  ifsc,
  account_number,
  handleRemoveAccount,
  onChange,
}) => (
  <Input.Group>
    <Input
      name={`ifsc__${id}`}
      class="Input--vTop"
      label="IFSC Code"
      defaultValue={ifsc}
      data-name="ifsc"
      placeholder="Enter IFSC code"
      maxLength={IFSC_LENGTH}
      onChange={onChange}
      validator={validateIFSC}
    />

    <Input
      name={`account_number__${id}`}
      class="Input--vTop"
      label="Account Number"
      data-name="account_number"
      placeholder="Enter acc. number"
      defaultValue={account_number}
      minLength={ACC_MIN_LENGTH}
      maxLength={ACC_MAX_LENGTH}
      onChange={onChange}
      validator={validateAccountNumber}
    />

    <i class="i i-delete-outline" onClick={handleRemoveAccount} />
  </Input.Group>
);

const validateIFSC = value => {
  if (value.length !== IFSC_LENGTH) {
    return 'Must be 11 characters long';
  }
};

const validateAccountNumber = value => {
  if (value.length < ACC_MIN_LENGTH) {
    return 'Must be at least 5 characters';
  }
};
