import ModalHeader from 'rzp/ui/ModalHeader';
import Form from 'component/Form';
import Input from 'component/Input';
import { showNotification } from 'rzp/modules/notifications';
import { closeModal } from 'rzp/modules/modals';
import Button from 'component/Button';

export default class extends React.PureComponent {
  onSave = data => {
    this.props.onSave(data.currency);
  };

  render() {
    return (
      <div class="PickCurrency-Modal">
        <ModalHeader
          title="Choose the Invoice Currency"
          onCloseClick={this.props.closeModal}
        />
        <div className="modal-body">
          <Form onSubmit={this.onSave}>
            <Input.CurrencySelect
              name="currency"
              parentQuerySelector=".ReactModal__Content"
              defaultValue={this.props.currency || 'INR'}
              fullDisplay
            />

            <small className="help-block">
              GST and tax related details will not show up for invoices with
              international currency
            </small>
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
