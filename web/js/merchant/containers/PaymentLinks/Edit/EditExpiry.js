import moment from 'moment';
import { dateCalculator } from 'component/Input/Calendar';
import { timeCalculator } from 'component/Input/Time';
import Input from 'component/Input';
import Time from 'rzp/ui/Time';
import Button, { AsyncBtn } from 'component/Button';

export default class EditExpiry extends React.Component {
  state = this.resetState();

  resetState() {
    return {
      isEditableMode: false,
      expire_by: this.props.value ? moment(this.props.value * 1000) : undefined,
      hasNoExpiry: this.props.value ? '0' : '1',
    };
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });

    this.props.trackerFn(this.props.entityId, 'Edit Expiry');
  };

  onDateChange = date => {
    const curExpiryByTime = this.state.expire_by;

    dateCalculator(date, curExpiryByTime, this.updateDate);
  };

  onTimeChange = date => {
    const curDate = this.state.expire_by;

    timeCalculator(date, curDate, this.updateDate);
  };

  updateDate = ts => {
    const newDate = moment(ts);

    this.setState({
      expire_by: newDate,
    });
  };

  render() {
    let content = (
      <React.Fragment>
        <Time value={this.props.value} format="DD MMM YYYY, hh:mm a" />
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
          <Input.Check
            fieldLabel="No Expiry"
            defaultValue={this.props.value ? '0' : '1'}
            value={this.state.hasNoExpiry}
            onChange={e => {
              if (e.target.value == '0') {
                // 0 => unselected
                setTimeout(() => {
                  document
                    .querySelector('[data-name="expire_by_date"]')
                    .focus();
                  document
                    .querySelector('[data-name="expire_by_date"]')
                    .click();
                }, 10);
              }
              this.setState({
                hasNoExpiry: e.target.value,
              });
            }}
          />
          <Input.Group class="InputGroup--near Input--half_big">
            <div class="Input-content">
              <Input.ToCalendar
                data-name="expire_by_date"
                placeholder="15-04-2018"
                defaultValue={this.state.expire_by}
                disabled={this.state.hasNoExpiry === '1'}
                readOnly={true}
                onChange={this.onDateChange}
                size="half"
                addonAfter={<i class="i i-date-range" />}
                placement="topLeft"
                allowToday={true}
                disablePastDates={true}
              />
              {!!this.state.expire_by && (
                <Input.TimePicker
                  placeholder="11:59PM"
                  defaultValue={this.state.expire_by}
                  disabled={this.state.hasNoExpiry === '1'}
                  readOnly={true}
                  onChange={this.onTimeChange}
                  size="half"
                  addonAfter={<i class="i i-time" />}
                />
              )}
            </div>
          </Input.Group>
          <div style={{ textAlign: 'right', marginBottom: 12, width: 192 }}>
            <Button.Transparent
              class="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
                this.props.trackerFn(this.props.entityId, 'Cancel Expiry');
              }}
            >
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              class="Button--small"
              style={{ marginRight: 0, marginLeft: 16 }}
              onClick={() => {
                return this.props
                  .editFn({
                    expire_by:
                      this.state.hasNoExpiry == '1'
                        ? null
                        : Math.floor(this.state.expire_by / 1000),
                  })
                  .then(resp => {
                    if (resp.data) {
                      this.setState(this.resetState());
                    }
                  });

                this.props.trackerFn(this.props.entityId, 'Save Expiry');
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
