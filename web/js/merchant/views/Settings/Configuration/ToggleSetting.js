import { Component } from 'react';
import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, camelize } from 'common/utils/rzp-utils';
import { updateFeatures } from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import SwitchField from 'common/ui/Forms/SwitchField';
import TextHighlighter from 'common/ui/TextHighlighter';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

class ToggleSetting extends Component {
  constructor(props) {
    super(props);
    this.state = {};

    if (props.features.length) {
      const featureFlagValue = this.getFeatureFlag(props.features);
      this.state.isFeatureFlagEnabled = props.isFeatureAPIKeyReversed
        ? !featureFlagValue
        : featureFlagValue;
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
    selfServerTrack = false,
    optionalProperties = {},
  ) => {
    const featureName = this.props.featureName;
    const analyticsLabel = camelize(featureName);
    const analyticsObjName = featureName.toLowerCase() || this.props.featureObjectName;

    if (isToggleTriggered) {
      selfServeTrackInitiate({
        selfServeAction: `${featureName} ${isFeatureEnabled ? 'Enabled' : 'Disabled'}`,
        page: 'Config',
        screen: 'Settings',
      });
    }
    if (selfServerTrack) {
      selfServeTrackSuccess({
        selfServeAction: `${featureName} ${isFeatureEnabled ? 'Enabled' : 'Disabled'}`,
        page: 'Config',
        screen: 'Settings',
      });
    }
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
        [this.props.featureAPIKey]: this.props.isFeatureAPIKeyReversed
          ? !isFeatureEnabled
          : isFeatureEnabled,
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

        this.analyticsForFeatureChange(false, isFeatureEnabled, true, { status: 'Success' });
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

      this.analyticsForFeatureChange(false, isFeatureEnabled, false, {
        status: 'Failure',
        failureReason: err.errors?.[0] || '',
      });
    }
  };

  render() {
    const { isFeatureFlagEnabled } = this.state;
    const { title, desc, hashedWith } = this.props;

    return (
      <div className="panel panel-default">
        <div className="panel-heading">
          <span className="title">
            <TextHighlighter hashedWith={hashedWith}>{title}</TextHighlighter>
          </span>

          <span className="toggler-btn">
            <SwitchField
              defaultChecked={!!isFeatureFlagEnabled}
              onChange={(isChecked, cb) => this.toggleFeatureSetting(isChecked, cb)}
              type="prime"
            />
            {isFeatureFlagEnabled ? (
              <b className="text-primary">Enabled</b>
            ) : (
              <b className="text-faded">Disabled</b>
            )}
          </span>
        </div>

        <div className="panel-body">
          <form className="form-horizontal">
            <div className="description">{desc}</div>
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
