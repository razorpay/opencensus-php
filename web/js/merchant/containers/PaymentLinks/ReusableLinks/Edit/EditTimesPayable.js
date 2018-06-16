import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';

export default class EditTimesPayable extends React.Component {
  state = this.resetState();

  resetState() {
    return {
      isEditableMode: false,
      timesPayable: this.props.value || '',
      hasNoLimit: this.props.value ? '0' : '1',
    };
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });

    this.props.trackerFn(this.props.entityId, 'Edit TimesPayable');
  };

  render() {
    let content = (
      <React.Fragment>
        {this.state.timesPayable || <span class="text-danger">No Limit</span>}
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
        <div class="InputGroup Input">
          <Input.Check
            fieldLabel="No Limit"
            name="hasNoLimit"
            defaultValue={this.state.hasNoLimit}
            value={this.state.hasNoExpiry}
            onChange={e => {
              this.setState({
                hasNoLimit: e.target.value,
              });

              if (e.target.value == '0') {
                setTimeout(
                  () => document.getElementsByName('times_payable')[0].focus(),
                  10
                );
              }
            }}
          />
          <Input
            name="times_payable"
            class="Input Input--small"
            placeholder="TimesPayable"
            value={this.state.timesPayable}
            disabled={this.state.hasNoLimit === '1'}
            validator={() => {
              if (this.state.hasNoLimit === '0' && !this.state.timesPayable) {
                return 'Please fill out this field';
              }
            }}
            onChange={e => {
              this.setState({
                timesPayable: e.target.value,
              });
            }}
          />
          <div
            style={{
              textAlign: 'right',
              marginBottom: 12,
              width: 260,
            }}
          >
            <Button.Transparent
              class="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
                this.props.trackerFn(
                  this.props.entityId,
                  'Cancel timesPayable'
                );
              }}
            >
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              class="Button--small"
              style={{ marginRight: 0, marginLeft: 16 }}
              disabled={
                this.state.hasNoLimit === '0' && !this.state.timesPayable
              }
              onClick={() => {
                this.props.trackerFn(this.props.entityId, 'Save TimesPayable');

                this.props
                  .editFn({
                    times_payable:
                      this.state.hasNoLimit == '1'
                        ? null
                        : this.state.timesPayable,
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
        </div>
      );
    }

    return content;
  }
}
