import { maxLength } from 'rzp/utils/validators';
import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';

export default class EditReceipt extends React.Component {
  state = this.resetState();

  resetState() {
    return {
      isEditableMode: false,
      receipt: this.props.value || '',
    };
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });
    setTimeout(() => document.getElementsByName('receipt_no')[0].focus(), 10);
    this.props.trackerFn(this.props.entityId, 'Edit Receipt');
  };

  render() {
    let content = (
      <React.Fragment>
        {this.state.receipt || '--'}
        <Button.Transparent
          onClick={this.makeEditable}
          class="Button--Link"
          style={{ marginLeft: 12 }}
        >
          Change
        </Button.Transparent>
      </React.Fragment>
    );

    if (this.state.isEditableMode) {
      content = (
        <React.Fragment>
          <Input
            name="receipt_no"
            placeholder="Receipt No."
            class="Input--small"
            value={this.state.receipt}
            validator={maxLength(40)}
            onChange={e => {
              this.setState({
                receipt: e.target.value,
              });
            }}
          />
          <div style={{ textAlign: 'right', marginBottom: 12, width: 260 }}>
            <Button.Transparent
              class="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
                this.props.trackerFn(this.props.entityId, 'Cancel Receipt');
              }}
            >
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              class="Button--small"
              style={{ marginRight: 0, marginLeft: 16 }}
              onClick={() => {
                this.props.trackerFn(this.props.entityId, 'Save Receipt');

                return this.props
                  .editFn({
                    receipt: this.state.receipt,
                  })
                  .then(resp => {
                    if (resp.data) {
                      this.setState(this.resetState());
                    }
                  });
              }}
              showLoader={false}
              pendingState="Saving..."
            >
              Save
            </AsyncBtn.Primary>
          </div>
        </React.Fragment>
      );
    }

    return content;
  }
}
