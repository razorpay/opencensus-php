import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';

export default class EditDescription extends React.Component {
  state = this.resetState();

  resetState() {
    return {
      isEditableMode: false,
      title: (this.props.value && this.props.value.title) || '',
      description: (this.props.value && this.props.value.description) || '',
    };
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });
    setTimeout(() => document.getElementsByName('title')[0].focus(), 10);
    this.props.trackerFn(this.props.entityId, 'Edit PaymentFor');
  };

  render() {
    let content = (
      <React.Fragment>
        <div>
          {this.state.title}
          {this.state.description && (
            <div class="label--secondary" style={{ whiteSpace: 'pre' }}>
              {this.state.description}
            </div>
          )}
        </div>
        <Button.Transparent onClick={this.makeEditable} class="Button--Link">
          Change
        </Button.Transparent>
      </React.Fragment>
    );

    if (this.state.isEditableMode) {
      content = (
        <div class="InputGroup Input">
          <Input
            name="title"
            class="Input--small"
            placeholder="PaymentFor"
            required={true}
            value={this.state.title}
            onChange={e => {
              this.setState({
                title: e.target.value,
              });
            }}
          />
          <Input.Textarea
            name="description"
            placeholder="Provide additional description"
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
                this.props.trackerFn(this.props.entityId, 'Cancel PaymentFor');
              }}
            >
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              class="Button--small"
              style={{ marginRight: 0, marginLeft: 16 }}
              disabled={!this.state.title}
              onClick={() => {
                this.props.trackerFn(this.props.entityId, 'Save PaymentFor');

                return this.props
                  .editFn({
                    title: this.state.title,
                    description: this.state.description,
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
        </div>
      );
    }

    return content;
  }
}
