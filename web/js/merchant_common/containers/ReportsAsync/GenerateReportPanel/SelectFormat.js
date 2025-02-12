import Input from 'common/new-ui/Input';
import { analyticsTrack } from 'common/utils/analytics';

import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { extractExtensionFromTemplate } from '../utils';

const DEFAULT_FILE_FORMAT = 'csv';
export default class SelectFormat extends React.Component {
  static getDerivedStateFromProps(nextProps, prevState = {}) {
    if (!prevState.selectedConfigId || nextProps.selectedConfigId !== prevState.selectedConfigId) {
      const value = getDefaultFileFormatOfConfig(nextProps.selectedConfigId, nextProps.allConfigs);
      return {
        selectedConfigId: nextProps.selectedConfigId,
        value,
      };
    }
    return null;
  }

  state = {};

  getValue = () => {
    const { state, props } = this;
    if (getDefaultFileFormatOfConfig(state.selectedConfigId, props.allConfigs) !== state.value) {
      return {
        template_overrides: {
          file_meta: {
            extension: state.value,
          },
        },
      };
    }
    return {};
  };

  onChange = ({ target }) => {
    const { value } = target;
    analyticsTrack({
      objectName: 'select format',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        format: value,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.setState({ value });
  };

  render() {
    return (
      <Input.Select
        label="Select Format"
        className="Input--vTop"
        name="selectedFormat"
        value={this.state.value}
        options={reportFormatOptions}
        size="half_big"
        onChange={this.onChange}
        disabled={this.props.isFormDisabled}
      />
    );
  }
}

function getDefaultFileFormatOfConfig(selectedConfigId, allConfigs) {
  const selectedConfig = allConfigs.find(({ id }) => id === selectedConfigId) || {};

  return extractExtensionFromTemplate(selectedConfig.template) || DEFAULT_FILE_FORMAT;
}

export const reportFormatOptions = [
  { name: 'csv', label: 'CSV' },
  { name: 'xlsx', label: 'Excel (xlsx)' },
  { name: 'xls', label: 'Old Excel (xls)' },
];
