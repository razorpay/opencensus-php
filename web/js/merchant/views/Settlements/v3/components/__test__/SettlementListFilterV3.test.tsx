import SettlementListFilterV3, {
  presetsForCalendar,
} from 'merchant/views/Settlements/v3/components/SettlementListFilter';
import { render, screen, userEvent, waitFor } from 'test-utils';
import React from 'react';
import moment from 'moment';
import { analyticsTrack } from 'common/utils/analytics';

const defaultProps = {
  terminalProviders: [1, 2],
  user: {},
  onSubmit: jest.fn(),
};

const renderApp = (props = {}, options = {}) => {
  return render(<SettlementListFilterV3 {...defaultProps} {...props} />, options);
};

const mockStartDate = moment(1680336000000);
const mockEndDate = moment(1680609600000);

jest.mock('common/ui/DateRangePickerV2', () =>
  Object.assign({
    __esModule: true,
    ...jest.requireActual('common/ui/DateRangePickerV2'),
    default: ({ presets, setSelectedPreset, onDatesChange, selectedPreset }) => {
      return (
        <div data-testid="date-range-picker-v2">
          <select
            name="Time Filter"
            onChange={(e) => {
              setSelectedPreset(presets.find((preset) => preset.name === e.target.value));
            }}
          >
            {presets.map((preset) => (
              <option value={preset.name} key={preset.name}>
                {preset.name}
              </option>
            ))}
          </select>
          <p data-testid="selected-preset">{selectedPreset.name}</p>
          <button type="button" onClick={() => onDatesChange(mockStartDate, mockEndDate)}>
            Change dates
          </button>
        </div>
      );
    },
  }),
);

jest.mock('merchant/components/ProviderSelector', () => ({
  __esModule: true,
  default: ({ providers }) => (
    <div data-testid="provider-selector">{providers.length} terminal providers</div>
  ),
}));

const validatePreset = async (preset) => {
  await waitFor(() => {
    expect(screen.getByTestId('selected-preset')).toHaveTextContent(preset.name);
  });
};

const selectPreset = async (preset) => {
  await userEvent.selectOptions(screen.getAllByRole('combobox')[0], preset.name);
  await validatePreset(preset);
};

const clickSearchButton = async () => {
  const submitBtn = screen.getByRole('button', { name: 'Search' });
  await userEvent.click(submitBtn);
};

const selectPresetAndValidateDate = async (preset, startDate, endDate) => {
  await selectPreset(preset);
  await userEvent.click(screen.getByRole('button', { name: 'Change dates' }));

  await clickSearchButton();
  expect(defaultProps.onSubmit).toHaveBeenCalledWith({
    from: startDate,
    terminal_id: '',
    to: endDate,
  });
};

const selectStatus = async (status) => {
  await userEvent.selectOptions(screen.getAllByRole('combobox')[1], status);
};

describe('SettlementListFilterV3', () => {
  test('should render date range picker, utr number, settlementId and status fields', () => {
    renderApp();

    // validate date range picker options
    expect(screen.getByText('Duration')).toBeInTheDocument();
    expect(screen.getByTestId('date-range-picker-v2')).toBeInTheDocument();
    presetsForCalendar.forEach((preset) => {
      expect(screen.getByRole('option', { name: preset.name }));
    });

    expect(screen.getByText('UTR number')).toBeInTheDocument();
    expect(screen.getByText('Settlement ID')).toBeInTheDocument();
    // check for utr and settlementid fields
    screen.getAllByRole('textbox').forEach((element) => {
      expect(['utr', 'id']).toContain((element as HTMLInputElement).name);
    });

    expect(screen.getByText('Status')).toBeInTheDocument();
    ['All', 'Created', 'Processed', 'Failed', 'Initiated'].forEach((option) => {
      expect(screen.getByRole('option', { name: option }));
    });

    // check for status and filter dropdowns
    screen.getAllByRole('combobox').forEach((element) => {
      expect(['status', 'Time Filter']).toContain((element as HTMLSelectElement).name);
    });
  });

  test('should set preset on preset selection', async () => {
    renderApp();
    const selectedPreset = presetsForCalendar[1];
    await selectPreset(selectedPreset);
  });

  test('should set start and end dates on date change', async () => {
    renderApp();
    await selectPresetAndValidateDate(
      presetsForCalendar[3],
      mockStartDate.unix(),
      mockEndDate.unix(),
    );
  });

  test('should set start and end dates as empty strings on date change when filter is all time', async () => {
    renderApp();
    await selectPresetAndValidateDate(presetsForCalendar[0], '', '');
  });

  test('should set 30 days preset when status is in query params and from/to are missing', async () => {
    renderApp({}, { initialEntries: ['/?status=all'] });
    await validatePreset(presetsForCalendar[2]);
  });

  test('should call analytics and set preset to 30 days on selecting status other than all', async () => {
    renderApp();
    await selectStatus('created');
    await validatePreset(presetsForCalendar[2]);

    expect(analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Settlement Status Drop down',
      actionName: 'Clicked',
      screen: 'Settlements',
      properties: {
        page: 'Home Screen',
        settlements_experiment_name: 'v2',
        sessionId: undefined,
      },
    });
  });

  test('should hide all option time filter is selected', async () => {
    renderApp();
    await selectStatus('created');
    await waitFor(() => {
      expect(
        screen.queryByRole('option', { name: presetsForCalendar[0].name }),
      ).not.toBeInTheDocument();
    });
  });

  test('should call on submit on clicking clear', async () => {
    renderApp();
    await userEvent.click(screen.getByRole('button', { name: 'Clear' }));
    expect(defaultProps.onSubmit).toHaveBeenCalledWith({});
  });

  test('should render payment provider if user is isSingleReconEnabled and isOptimizerEnabled', () => {
    renderApp({
      user: {
        isSingleReconEnabled: true,
        isOptimizerEnabled: true,
      },
    });

    expect(screen.getByText('Payment Provider')).toBeInTheDocument();
    expect(screen.getByTestId('provider-selector')).toBeInTheDocument();
    expect(
      screen.getByText(`${defaultProps.terminalProviders.length} terminal providers`),
    ).toBeInTheDocument();
  });
});
