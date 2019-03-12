import moment from 'moment';
import { dateCalculator } from 'component/Input/Calendar';
import { timeCalculator } from 'component/Input/Time';
import Input from 'component/Input';
import Time from 'rzp/ui/Time';
import Button, { AsyncBtn } from 'component/Button';

import { classList } from 'common/util';

export default class EditExpiry extends React.Component {
  state = this.resetState();

  resetState(props) {
    props = props || this.props;

    return {
      isEditableMode: false,
      expire_by: props.value ? moment(props.value * 1000) : undefined,
    };
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.value * 1000 !== this.state.expire_by) {
      this.setState(this.resetState(nextProps));
    }
  }

  makeEditable = () => {
    this.setState({
      isEditableMode: true,
    });

    this.props.trackerFn && this.props.trackerFn('Edit Expiry');
  };

  updateDate = newDate => {
    this.setState({ expire_by: newDate });
  };

  render() {
    const { isRoleAllowedEdit, isExpireByRequired } = this.props;
    let content = (
      <React.Fragment>
        {this.props.value ? (
          <Time value={this.props.value} format="DD MMM YYYY, hh:mm a" />
        ) : (
          'No Expiry'
        )}

        {isRoleAllowedEdit && (
          <Button.Transparent
            onClick={this.makeEditable}
            class="Button--Link"
            style={{ marginLeft: 12 }}
          >
            Change
          </Button.Transparent>
        )}
      </React.Fragment>
    );

    if (this.state.isEditableMode) {
      content = (
        <React.Fragment>
          <DateField
            updateDate={this.updateDate}
            expire_by={this.state.expire_by}
            defaultValue={this.state.expire_by}
            isExpireByRequired={isExpireByRequired}
          />

          <div style={{ textAlign: 'right', marginBottom: 12, width: 192 }}>
            <Button.Transparent
              class="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
                this.props.trackerFn &&
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
                    expire_by: this.state.expire_by,
                  })
                  .then(resp => {
                    if (resp && resp.data) {
                      this.setState(this.resetState());

                      this.props.trackerFn &&
                        this.props.trackerFn('Edit Expiry (Saved)');
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

export class DateField extends React.Component {
  state = {
    expire_by: this.props.defaultValue,
    hasNoExpiry: this.props.isExpireByRequired
      ? false
      : !this.props.defaultValue,
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

    this.props.updateDate && this.props.updateDate(newDate);
  };

  render() {
    const { expire_by, hasNoExpiry } = this.state;
    const {
      isInline,
      label,
      className,
      isExpireByRequired = false,
    } = this.props;

    return (
      <React.Fragment>
        {!isExpireByRequired && (
          <Input.Check
            label={label}
            className={className}
            fieldLabel="No Expiry"
            defaultValue={this.props.defaultValue ? '0' : '1'}
            value={hasNoExpiry}
            onChange={e => {
              if (!e.target.checked) {
                setTimeout(() => {
                  document
                    .querySelector('[data-name="expire_by_date"]')
                    .focus();
                  document
                    .querySelector('[data-name="expire_by_date"]')
                    .click();
                }, 10);

                this.props.updateDate && this.props.updateDate(null);
              }

              this.setState({
                hasNoExpiry: e.target.checked,
              });
            }}
          />
        )}
        <Input.Group
          class={classList(
            !isExpireByRequired && 'InputGroup--near',
            isInline ? 'InputGroup--inline' : 'Input--half_big'
          )}
        >
          <div
            class="Input-content"
            style={{ marginTop: isExpireByRequired ? -8 : 0 }}
          >
            <Input.ToCalendar
              data-name="expire_by_date"
              placeholder="15-04-2018"
              defaultValue={moment(expire_by)}
              disabled={hasNoExpiry}
              readOnly={true}
              onChange={this.onDateChange}
              size={isInline ? 'half_small' : 'half'}
              addonAfter={<i class="i i-date-range" />}
              placement="topLeft"
              allowToday={true}
              disablePastDates={true}
              required={isExpireByRequired}
            />
            {!!expire_by && (
              <Input.TimePicker
                placeholder="11:59PM"
                defaultValue={moment(expire_by)}
                disabled={hasNoExpiry}
                readOnly={true}
                onChange={this.onTimeChange}
                size={isInline ? 'half_small' : 'half'}
                addonAfter={<i class="i i-time" />}
                required={isExpireByRequired}
              />
            )}
          </div>
        </Input.Group>
      </React.Fragment>
    );
  }
}
