import React from 'react';
import { maxLength } from 'common/utils/validators';
import Input from 'common/new-ui/Input';
import Button, { AsyncBtn } from 'common/new-ui/Button';

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
    this.props.trackerFn('Edit Receipt');
  };

  render() {
    const {
      isRoleAllowedEdit,
      isPaymentlinksV2Enabled,
      cancelTrackerfn,
      saveTrackerFn,
    } = this.props;

    let content = (
      <React.Fragment>
        {this.state.receipt || '--'}
        {isRoleAllowedEdit && (
          <Button.Transparent
            onClick={this.makeEditable}
            className="Button--Link"
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
          <Input
            name="receipt_no"
            placeholder={isPaymentlinksV2Enabled ? 'Reference Id' : 'Receipt No.'}
            className="Input--small"
            value={this.state.receipt}
            validator={maxLength(40)}
            required={this.props.required}
            onChange={(e) => {
              this.setState({
                receipt: e.target.value,
              });
            }}
          />
          <div style={{ textAlign: 'right', marginBottom: 12, width: 260 }}>
            <Button.Transparent
              className="Button--Link"
              onClick={() => {
                this.setState(this.resetState());
                cancelTrackerfn && cancelTrackerfn();
                this.props.trackerFn(
                  this.props.entityId,
                  'Cancel Receipt',
                  this.props.value !== this.state.receipt,
                );
              }}
            >
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              className="Button--small"
              style={{ marginRight: 0, marginLeft: 16 }}
              onClick={() => {
                return this.props
                  .editFn({
                    receipt: this.state.receipt,
                  })
                  .then((resp) => {
                    if (resp && resp.data) {
                      this.setState(this.resetState());
                    }
                    saveTrackerFn && saveTrackerFn();
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
