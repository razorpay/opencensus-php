import React from 'react';

import { render, screen, userEvent } from 'test-utils';

import { Formats } from '..';
import { FormatsProps } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/components/Formats/types';
import {
  DEFAULT_FORMATS,
  DELIMITER_ERROR_TEXT,
  DELIMITER_PLACEHOLDER,
  DELIMITER_SUPPORT_MAP,
  FORMATS_PLACEHOLDER,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/components/Formats/constants';

const mockProps: FormatsProps = {
  availableFormats: DEFAULT_FORMATS,
  selectedFormat: undefined,
  selectedDelimiter: undefined,
  setSelectedFormat: jest.fn(),
  setSelectedDelimiter: jest.fn(),
  showErrorInSection: undefined,
};

function renderApp(props = {}) {
  return render(<Formats {...mockProps} {...props} />);
}

describe('Formats', () => {
  test('should be able to select and see the selected option from the available format options array', async () => {
    const formatLabel = DEFAULT_FORMATS[0].label;

    renderApp();

    const selectedFormatEl = screen.getByPlaceholderText(FORMATS_PLACEHOLDER);

    // Click the formats input element to open dropdown.
    await userEvent.click(selectedFormatEl);
    // Select the first option from the format options dropdown.
    await userEvent.click(screen.getByTestId(formatLabel));

    expect(selectedFormatEl).toHaveTextContent(formatLabel);
  });

  test('should not show delimiter input when there is no available delimiter options for that selected format', async () => {
    const { label: formatLabel = '' } = DEFAULT_FORMATS.find(({ value }) => value === 'xlsx') || {};

    renderApp();

    const selectedFormatEl = screen.getByPlaceholderText(FORMATS_PLACEHOLDER);

    // Click the formats input element to open dropdown.
    await userEvent.click(selectedFormatEl);
    // Select the first option from the format options dropdown.
    await userEvent.click(screen.getByTestId(formatLabel));

    expect(screen.queryByTestId('delimiterInput')).not.toBeInTheDocument();
  });

  test('should show error when format is selected but delimiter is not selected', async () => {
    const formatLabel = DEFAULT_FORMATS[0].label;
    const setSelectedDelimiter = jest.fn().mockReturnValueOnce(null);

    renderApp({ setSelectedDelimiter, showErrorInSection: 0 });

    const selectedFormatEl = screen.getByPlaceholderText(FORMATS_PLACEHOLDER);

    // Click the formats input element to open dropdown.
    await userEvent.click(selectedFormatEl);
    // Select the first option from the format options dropdown.
    await userEvent.click(screen.getByTestId(formatLabel));

    expect(screen.getByText(DELIMITER_ERROR_TEXT)).toBeInTheDocument();
  });

  test('should be able to select and see the selected option from the available delimiter options array', async () => {
    const { label: formatLabel = '', value: formatValue } =
      DEFAULT_FORMATS.find(({ value }) => value === 'txt') || {};
    const { label: delimiterLabel } = DELIMITER_SUPPORT_MAP[formatValue || '']?.[0] || {};

    renderApp();

    // Click the formats input element to open dropdown.
    await userEvent.click(screen.getByPlaceholderText(FORMATS_PLACEHOLDER));
    // Select the first option from the format options dropdown.
    await userEvent.click(screen.getByTestId(formatLabel));

    const delimiterEl = screen.getByPlaceholderText(DELIMITER_PLACEHOLDER);

    // Click the delimiter input element to open dropdown.
    await userEvent.click(delimiterEl);
    // Select the options from the delimiter options dropdown.
    await userEvent.click(screen.getByTestId(delimiterLabel));

    expect(delimiterEl).toHaveTextContent(delimiterLabel);
  });
});
