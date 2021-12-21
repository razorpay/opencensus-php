import React from 'react';

import moment from 'moment';
import Input from 'common/new-ui/Input';
import Time from 'common/ui/Time';
import Button, { AsyncBtn } from 'common/new-ui/Button';

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

  updateDate = (newDate) => {
    this.setState({ expire_by: newDate });
  };

  render() {
    const { isRoleAllowedEdit, isExpireByRequired, entityName } = this.props;
    const label = entityName === 'virtual_account' ? 'No closing date' : 'No Expiry';
    let content = (
      <React.Fragment>
        {this.props.value ? (
          <Time value={this.props.value} format="DD MMM YYYY, hh:mm a" class="mr-12" />
        ) : (
          <span className="close-by-value">{label}</span>
        )}

        {isRoleAllowedEdit && (
          <Button.Transparent onClick={this.makeEditable} class="Button--Link">
            Change
          </Button.Transparent>
        )}
      </React.Fragment>
    );

    if (this.state.isEditableMode) {
      content = (
        <React.Fragment>
          <Input.DateTime
            checkboxFieldLabel={label}
            value={this.state.expire_by}
            defaultValue={this.state.expire_by}
            required={isExpireByRequired}
            onChange={this.updateDate}
          />

          <div style={{ textAlign: 'right', marginBottom: 12, width: 192 }}>
            <Button.Transparent
              class="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
                this.props.trackerFn &&
                  this.props.trackerFn(
                    this.props.entityId,
                    'Cancel Expiry',
                    this.state.expire_by !== this.state.value,
                  );
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
                  .then((resp) => {
                    if (resp && resp.data) {
                      this.setState(this.resetState());

                      this.props.trackerFn &&
                        this.props.trackerFn('Edit Expiry (Saved)', this.state.expire_by);
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
