import React from 'react';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import Tooltip from 'common/ui/Tooltip';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export default class extends React.PureComponent {
  state = { isEditable: false };

  handleOnInput = ({ target }) => {
    this.autoAdjustHeight(target);
  };

  autoAdjustHeight(target) {
    if (!target) {
      return;
    }

    const content = target.value;
    const fakeEle = window.document.querySelector('#terms-details .fake-textarea');

    fakeEle.value = content;
    const newHeight = fakeEle.scrollHeight;

    target.style.height = `${newHeight}px`;
  }

  componentDidMount() {
    this.autoAdjustHeight(document.body.querySelector('#terms-details textarea[name="terms"]'));
  }

  render() {
    const isEditable = this.state.isEditable || this.props.terms;
    const { isPPNewFooterUX } = this.props;

    const content = (
      <div id="terms-details">
        {isEditable ? (
          <React.Fragment>
            <textarea class="fake-textarea" readOnly />
            <label>Terms & Conditions:</label>
            <Input.Textarea
              name="terms"
              placeholder="Enter Terms & Conditions"
              defaultValue={this.props.terms}
              onInput={this.handleOnInput}
              onBlur={(e) => {
                this.setState({ isEditable: false });
                this.props.updateData(e);
              }}
              // eslint-disable-next-line consistent-return
              validator={(val) => {
                if (!val) {
                  return '';
                } else if (val.length < 5) {
                  return 'Value should be minimum 5 characters';
                }
              }}
              minLength="5"
              autoFocus
            />
          </React.Fragment>
        ) : (
          <Button.Transparent
            class="btn-link"
            onClick={() => {
              analyticsTrack({
                objectName: 'terms',
                actionName: 'added',
                screen: 'create payment page',
                properties: {
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              this.setState({ isEditable: true });
            }}
          >
            + Add Terms & Conditions
          </Button.Transparent>
        )}
      </div>
    );

    const contentV2 = (
      <div id="terms-details">
        {isEditable ? (
          <React.Fragment>
            <textarea class="fake-textarea" readOnly />
            <label>Terms & Conditions:</label>
            <Input.Textarea
              name="terms"
              placeholder="Enter Terms & Conditions"
              defaultValue={this.props.terms}
              onInput={this.handleOnInput}
              onBlur={(e) => {
                this.setState({ isEditable: false });
                this.props.updateData(e);
              }}
              // eslint-disable-next-line consistent-return
              validator={(val) => {
                if (!val) {
                  return '';
                } else if (val.length < 5) {
                  return 'Value should be minimum 5 characters';
                }
              }}
              minLength="5"
              autoFocus
            />
          </React.Fragment>
        ) : (
          <>
            <Button.Transparent
              class="btn-link"
              onClick={() => {
                analyticsTrack({
                  objectName: 'terms',
                  actionName: 'added',
                  screen: 'create payment page',
                  properties: {
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                this.setState({ isEditable: true });
              }}
            >
              + Add Your Terms and Conditions
            </Button.Transparent>
            <br />
          </>
        )}
        <div>
          <p>
            You agree to share information entered on this page with Better Experience (owner of
            this page) and Razorpay, adhering to applicable laws.
          </p>
          <Tooltip theme="dark" align="top" className="rzp-tooltip-tnc">
            These terms and conditions are mandatory and cannot be removed.
          </Tooltip>
        </div>
      </div>
    );

    return isPPNewFooterUX ? contentV2 : content;
  }
}
