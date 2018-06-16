import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';

export default class EditDescription extends React.Component {
  state = this.resetState();

  resetState() {
    return {
      isEditableMode: false,
      description: this.props.value || '',
    };
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });
    setTimeout(() => document.getElementsByName('description')[0].focus(), 10);
    this.props.trackerFn(this.props.entityId, 'Edit Description');
  };

  render() {
    let content = (
      <React.Fragment>
        {this.state.description || '--'}
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
          <Input.Textarea
            name="description"
            placeholder="Payment Description"
            class="Input--small"
            value={this.state.description}
            onChange={e => {
              this.setState({
                description: e.target.value,
              });
            }}
          />
          <div
            style={{
              textAlign: 'right',
              marginBottom: 12,
              width: 260,
              marginTop: -8,
            }}
          >
            <Button.Transparent
              class="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
                this.props.trackerFn(
                  this.props.paymentLinkId,
                  'Cancel Description'
                );
              }}
            >
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              class="Button--small"
              style={{ marginRight: 0, marginLeft: 16 }}
              disabled={!this.state.description}
              onClick={() => {
                this.props.trackerFn(
                  this.props.paymentLinkId,
                  'Save Description'
                );

                this.props
                  .editFn({
                    description: this.state.description,
                  })
                  .then(resp => {
                    if (resp.data) {
                      this.setState(this.resetState());
                    }
                  });
              }}
              pendingState="Saving"
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
