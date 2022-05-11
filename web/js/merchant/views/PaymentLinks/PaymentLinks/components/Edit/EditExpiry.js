import React from 'react';

import moment from 'moment';
import Input from 'common/new-ui/Input';
import Time from 'common/ui/Time';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { DocLink } from 'merchant/components/DocsLink';

export default class EditExpiry extends React.Component {
  state = this.resetState();

  resetState(props) {
    props = props || this.props;

    return {
      isEditableMode: false,
      expire_by: props.value ? moment(props.value * 1000) : undefined,
    };
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const { expire_by } = this.state;
    if (nextProps.value * 1000 !== expire_by) {
      this.setState(this.resetState(nextProps));
    }
  }

  makeEditable = () => {
    const { trackerFn } = this.props;
    this.setState({
      isEditableMode: true,
    });

    trackerFn?.('Edit Expiry');
  };

  updateDate = (newDate) => {
    this.setState({ expire_by: newDate });

    const { trackerFn } = this.props;

    if (trackerFn) {
      if (newDate) {
        trackerFn(null, 'Update Date');
      } else {
        trackerFn(null, 'No Expiry');
      }
    }
  };

  handleCancel = () => {
    const { trackerFn, entityId, cancelTrackerfn } = this.props;
    const { expire_by, value } = this.state;
    this.setState(this.resetState());
    trackerFn?.(entityId, 'Cancel Expiry', expire_by !== value);
    cancelTrackerfn && cancelTrackerfn();
  };

  handleSave = () => {
    const { editFn, trackerFn, saveTrackerFn } = this.props;
    const { expire_by } = this.state;
    saveTrackerFn && saveTrackerFn();
    return editFn({
      expire_by,
    }).then((resp) => {
      if (resp && resp.data) {
        this.setState(this.resetState());
        trackerFn?.('Edit Expiry (Saved)', expire_by);
      }
    });
  };

  render() {
    const { isRoleAllowedEdit, isExpireByRequired, entityName, value } = this.props;
    const { isEditableMode, expire_by } = this.state;
    const label = entityName === 'virtual_account' ? 'No closing date' : 'No Expiry';
    const docUrl = 'https://razorpay.com/docs/payments/smart-collect/update-individual-expiry/';
    let content = (
      <React.Fragment>
        {value ? (
          <Time value={value} format="DD MMM YYYY, hh:mm a" className="mr-12" />
        ) : (
          <span className="close-by-value">{label}</span>
        )}

        {isRoleAllowedEdit && (
          <>
            <Button.Transparent onClick={this.makeEditable} className="Button--Link">
              Change
            </Button.Transparent>
            {entityName === 'virtual_account' && (
              <small className="help-content">
                <i className="i i-info-outline" />
                <Popover align="top" theme="dark">
                  <PopoverBody>
                    <DocLink className="btn btn-link" href={docUrl}>
                      View Documentation <i className="i i-external-link" />
                    </DocLink>
                  </PopoverBody>
                </Popover>
              </small>
            )}
          </>
        )}
      </React.Fragment>
    );

    if (isEditableMode) {
      content = (
        <React.Fragment>
          <Input.DateTime
            checkboxFieldLabel={label}
            value={expire_by}
            defaultValue={expire_by}
            required={isExpireByRequired}
            onChange={this.updateDate}
          />

          <div className="edit-expiry">
            <Button.Transparent className="Button--Link" onClick={this.handleCancel}>
              Cancel
            </Button.Transparent>

            <AsyncBtn.Primary
              className="Button--small save"
              onClick={this.handleSave}
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
