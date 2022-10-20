import SelectFormat, {
  reportFormatOptions,
} from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectFormat';
import { fireEvent, render, screen } from 'test-utils';
import * as analytics from 'common/utils/analytics';

describe('SelectFormat', () => {
  const allConfigs = [
    {
      id: 'config_JWDlBNXBHpftdA',
      name: 'SubMerchant Report for Platform Partner',
      template: {
        file_meta: {
          delimiter: ',',
          extension: 'xls',
          filename: 'SubMerchant Report for Platform Partner',
          header: true,
        },
      },
    },
    {
      id: 'config_JG46UvOwIHV3fm',
      name: 'Retry Payment Links',
      template: {},
    },
  ];

  const defaultProps = {
    isFormDisabled: false,
    selectedConfigId: undefined,
    allConfigs,
  };

  let _appRef = {};

  const App = (props = {}) => (
    <SelectFormat ref={(ref) => (_appRef = ref)} {...defaultProps} {...props} />
  );

  test('should render Select Format', () => {
    render(<App />);
    expect(screen.getByText('Select Format')).toBeInTheDocument();
    expect(screen.getAllByRole('option')).toHaveLength(reportFormatOptions.length);
  });

  test('should setValue and call analytics on format selection', () => {
    const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');

    render(<App />);

    const selectFormatDropdown = screen.getByRole('combobox', { name: '' });

    fireEvent.change(selectFormatDropdown, { target: { value: reportFormatOptions[0].name } });

    expect(analyticsTrackSpy).toHaveBeenCalledWith({
      objectName: 'select format',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        format: reportFormatOptions[0].name,
      },
    });
  });

  test('should set default value for a selected config', () => {
    const { rerender } = render(
      <App allConfigs={allConfigs} selectedConfigId={allConfigs[0].id} />,
    );

    expect(screen.getByRole('combobox')).toHaveValue(allConfigs[0].template.file_meta.extension);

    rerender(<App allConfigs={allConfigs} selectedConfigId={allConfigs[1].id} />);
    // csv is default format when there's no template
    const selectFormatDropdown = screen.getByRole('combobox');
    expect(selectFormatDropdown).toHaveValue('csv');
    // this is for the condition in getDerivedStateFromProps
    fireEvent.change(selectFormatDropdown, { target: { value: reportFormatOptions[2].name } });
    expect(selectFormatDropdown).toHaveValue(reportFormatOptions[2].name);
  });

  test('test getValue method', () => {
    // getValue method is accessed using ref in parent components to access the value
    render(<App allConfigs={allConfigs} selectedConfigId={allConfigs[0].id} />);
    const selectFormatDropdown = screen.getByRole('combobox');
    // when current value is csv (default format)
    expect(_appRef.getValue()).toStrictEqual({});
    // when current value is different from config's default value
    fireEvent.change(selectFormatDropdown, { target: { value: reportFormatOptions[1].name } });
    expect(_appRef.getValue()).toStrictEqual({
      template_overrides: { file_meta: { extension: reportFormatOptions[1].name } },
    });
  });
});
