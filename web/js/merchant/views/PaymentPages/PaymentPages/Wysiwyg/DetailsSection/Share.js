import React from 'react';

import Popover, { PopoverBody } from 'common/ui/Popover';
import Button from 'common/new-ui/Button';
import RemoveBtn from 'merchant/views/PaymentPages/PaymentPages/components/RemoveBtn';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import track from '../track';

export default class extends React.PureComponent {
  onUpdate = (allowSocialShare) => {
    this.props.updateData({
      target: {
        name: 'allow_social_share',
        value: allowSocialShare,
      },
    });

    allowSocialShare && track.wysiwyg.addSocialMediaIcons();
  };

  render() {
    const allowSocialShare = this.props.allowSocialShare;

    const Icons = (
      <React.Fragment>
        <span className="facebook" />
        <span className="twitter" />
        <span className="whatsapp" />
      </React.Fragment>
    );

    return (
      <div id="share-details">
        {allowSocialShare ? (
          <React.Fragment>
            <label>Share this on:</label>
            <div className="share-icons">
              {Icons}
              <RemoveBtn onClick={() => this.onUpdate(false)} />
            </div>
          </React.Fragment>
        ) : (
          <span className="help-content">
            <Button.Transparent
              className="btn-link"
              onClick={() => {
                analyticsTrack({
                  objectName: 'social share',
                  actionName: 'added',
                  screen: 'create payment page',
                  properties: {
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                this.onUpdate(true);
              }}
            >
              + Add social media share icons
            </Button.Transparent>
            <Popover align="right" theme="dark">
              <PopoverBody>Allow your customers to share the page on social media</PopoverBody>
            </Popover>
          </span>
        )}
      </div>
    );
  }
}
