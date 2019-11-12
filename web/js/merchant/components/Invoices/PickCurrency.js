import ModalHeader from 'common/ui/ModalHeader';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal } from 'merchant_common/reducers/modals';
import Button from 'common/new-ui/Button';
import Alert from 'common/new-ui/Alert';

export default class extends React.PureComponent {
  state = {
    currencySelected: this.props.currency,
  };

  onSave = data => {
    this.props.onSave(data.currency);
    this.props.closeModal();
  };

  onChange = option => {
    this.setState({ currencySelected: option.name });
  };

  render() {
    const alerts = [
      'GST and tax related details will not show up for invoices with international currency.',
    ];

    if (this.state.currencySelected !== 'INR') {
      alerts.push(
        'The rate of all the items in the current invoice will reset to 0.'
      );
    }

    return (
      <div class="PickCurrency-Modal">
        <ModalHeader
          title="Choose the Invoice Currency"
          onCloseClick={
            this.props.showCross ? this.props.closeModal : undefined
          }
        />
        <div className="modal-body">
          <Form onSubmit={this.onSave}>
            <Input.CurrencySelect
              name="currency"
              parentQuerySelector=".ReactModal__Content"
              defaultValue={this.props.currency || 'INR'}
              fullDisplay
              onChange={this.onChange}
            />

            <Alert.Warning>
              <b>NOTE: </b>
              {alerts.length > 1 && <br />}

              {alerts.map((msg, ix) => (
                <span key={ix}>
                  {ix > 0 && <br />}
                  {alerts.length > 1 ? ix + 1 + '. ' : ''} {msg}
                </span>
              ))}

              <br />
            </Alert.Warning>
            <br />
            <Button.Primary class="btn-block">
              Continue to Invoice
            </Button.Primary>
          </Form>
        </div>
      </div>
    );
  }
}
