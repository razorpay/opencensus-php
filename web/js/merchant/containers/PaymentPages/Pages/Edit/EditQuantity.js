import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';
import { isInteger } from 'rzp/utils/validators';

export default class EditQuantity extends React.Component {
  state = this.resetState(this.props);

  resetState(props) {
    props = props || this.props;

    return {
      isEditableMode: false,
      timesPayable: props.value || '',
      hasNoLimit: props.value ? '0' : '1',
    };
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.value !== this.state.timesPayable) {
      this.setState(this.resetState(nextProps));
    }
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });

    this.props.trackerFn('Edit Quantity');
  };

  render() {
    const { isRoleAllowedEdit, timesPaid } = this.props;

    let content = (
      <React.Fragment>
        {timesPaid}
        <span style={{ opacity: 0.7 }}>
          {this.state.timesPayable && ' of ' + this.state.timesPayable}
        </span>
        {isRoleAllowedEdit && (
          <Button.Transparent
            onClick={this.makeEditable}
            class="Button--Link pull-right"
          >
            Update Quantity
          </Button.Transparent>
        )}
      </React.Fragment>
    );

    if (this.state.isEditableMode) {
      content = (
        <div class="InputGroup Input" style={{ maxWidth: 260 }}>
          <Input.Check
            fieldLabel="No Limit"
            name="hasNoLimit"
            defaultValue={this.state.hasNoLimit}
            value={this.state.hasNoLimit}
            onChange={e => {
              this.setState({
                hasNoLimit: e.target.value,
              });

              if (e.target.value == '0') {
                setTimeout(() => {
                  const ele = document.getElementsByName('times_payable');
                  ele[0] && ele[0].focus();
                }, 10);
              }
            }}
          />
          <Input
            name="times_payable"
            class="Input"
            placeholder="Total Quantity"
            value={this.state.timesPayable}
            disabled={this.state.hasNoLimit === '1'}
            onFocus={e => {
              e.target.select();
            }}
            validator={val => {
              if (this.state.hasNoLimit === '0') {
                if (!this.state.timesPayable) {
                  return 'Please fill out this field';
                } else if (!isInteger(val)) {
                  return 'Enter valid number';
                }
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
            }}
          >
            <Button.Transparent
              class="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
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
                return this.props
                  .editFn({
                    times_payable:
                      this.state.hasNoLimit == '1'
                        ? null
                        : Number(this.state.timesPayable),
                  })
                  .then(resp => {
                    if (resp && resp.data) {
                      this.setState(this.resetState());
                      this.props.trackerFn('Edit Quantity (Saved)');
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
