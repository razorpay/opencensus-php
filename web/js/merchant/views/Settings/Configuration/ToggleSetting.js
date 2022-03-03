import { Component } from 'react';
import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, camelize } from 'common/utils/rzp-utils';
import { updateFeatures } from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import SwitchField from 'common/ui/Forms/SwitchField';
import TextHighlighter from 'common/ui/TextHighlighter';
class ToggleSetting extends Component {
  constructor(props) {
    super(props);
    this.state = {};

    if (props.features.length) {
      const featureFlagValue = this.getFeatureFlag(props.features);
      this.state.isFeatureFlagEnabled = featureFlagValue;
    }
  }

  getFeatureFlag(features) {
    const featureObj =
      features.find((feature) => feature.feature === this.props.featureAPIKey) || {};
    return featureObj.value;
  }

  analytics = (action) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - ${this.props.featureName}`,
    });
  };

  analyticsForFeatureChange = (
    isToggleTriggered = false,
    isFeatureEnabled,
    optionalProperties = {},
  ) => {
    const featureName = this.props.featureName;
    const analyticsLabel = camelize(featureName);
    const analyticsObjName = featureName.toLowerCase() || this.props.featureObjectName;

    analyticsTrack({
      objectName: isToggleTriggered ? analyticsObjName : `${analyticsObjName} toggle`,
      actionName: isToggleTriggered ? 'toggled' : 'result',
      screen: 'settings',
      properties: {
        location: 'configuration',
        [analyticsLabel]: isFeatureEnabled ? 'Enabled' : 'Disabled',
        ...optionalProperties,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  toggleFeatureSetting = async (isFeatureEnabled, cb) => {
    const shouldSync = 0;
    const data = {
      features: {
        [this.props.featureAPIKey]: isFeatureEnabled,
      },
      should_sync: shouldSync,
    };

    this.analyticsForFeatureChange(true, isFeatureEnabled);

    try {
      const response = await this.props.updateFeatures(data, this.props.user.current);

      if (response) {
        cb(true);

        if (isFeatureEnabled) {
          this.analytics('Enable');
        } else {
          this.analytics('Disable');
        }
        this.props.showNotification({
          type: 'success',
          message: 'Your preference was saved',
        });

        this.analyticsForFeatureChange(false, isFeatureEnabled, { status: 'Success' });
        this.setState((prevState) => {
          return {
            isFeatureFlagEnabled: !prevState.isFeatureFlagEnabled,
          };
        });
      }
    } catch (err) {
      cb(false);

      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });

      this.analyticsForFeatureChange(false, isFeatureEnabled, {
        status: 'Failure',
        failureReason: err.errors?.[0] || '',
      });
    }
  };

  render() {
    const { isFeatureFlagEnabled } = this.state;
    const { title, desc, hashedWith } = this.props;

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">
            <TextHighlighter hashedWith={hashedWith}>{title}</TextHighlighter>
          </span>

          <span class="toggler-btn">
            <SwitchField
              defaultChecked={!!isFeatureFlagEnabled}
              onChange={(isChecked, cb) => this.toggleFeatureSetting(isChecked, cb)}
              type="prime"
            />
            {isFeatureFlagEnabled ? (
              <b class="text-primary">Enabled</b>
            ) : (
              <b class="text-faded">Disabled</b>
            )}
          </span>
        </div>

        <div class="panel-body">
          <form class="form-horizontal">
            <div class="description">{desc}</div>
          </form>
        </div>
      </div>
    );
  }
}

export default connect(
  (state) => {
    return {
      user: state.session.user,
      features: state.config.features,
    };
  },
  { updateFeatures, showNotification },
)(ToggleSetting);
