import React from 'react';
import moment from 'moment';

import { STORE_TYPE_MAP } from 'merchant/views/BillMeSettings/common/constants';
import TerminalsTableComponent from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/TerminalsTableComponent/TerminalsTableComponent';
import { TERMINALS_DATA } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/__tests__/mocks';
import { screen, render, fireEvent, waitFor, userEvent } from 'test-utils';

const App = ({ props }) => <TerminalsTableComponent {...props} />;

const TERMINALS_TABLE_PROPS = {
  isRefreshing: false,
  defaultPageSize: 10,
  totalItemCount: 100,
};

describe('TerminalsTableComponent', () => {
  afterEach(() => {
    window.sessionStorage.setItem('storeTerminalStatusAlertAcknowledged', 'false');
  });

  test("should render 'TerminalsTableComponent' component as expected", async () => {
    const changePage = jest.fn();
    const changePageSize = jest.fn();
    const onToggleStatus = jest.fn();
    const props = {
      tableProps: {
        ...TERMINALS_TABLE_PROPS,
        changePage,
        changePageSize,
        currentPage: 0,
        terminalsData: TERMINALS_DATA,
      },
      onToggleStatus,
    };

    render(<App props={props} />);

    // Terminals table info
    // Table header row
    expect(screen.getByText('Store Code & Details')).toBeInTheDocument();
    expect(screen.getByText('Terminal Key')).toBeInTheDocument();
    expect(screen.getByText('POS Details')).toBeInTheDocument();
    expect(screen.getByText('Last Transaction On')).toBeInTheDocument();
    expect(screen.getByText('Last Updated At')).toBeInTheDocument();
    expect(screen.getByText('Version & BIT')).toBeInTheDocument();
    expect(screen.getByText('Status')).toBeInTheDocument();

    // Table value row
    const rowInfo = TERMINALS_DATA[0];
    expect(
      screen.getByText(`${rowInfo.store.storeInfo.storeCode} - ${rowInfo.store.name}`),
    ).toBeInTheDocument();
    expect(
      screen.getByText(STORE_TYPE_MAP[rowInfo.store.storeInfo.storeType].label),
    ).toBeInTheDocument();
    expect(screen.getByText(rowInfo.terminalInfo.licenseKey)).toBeInTheDocument();
    expect(
      screen.getByText(`${rowInfo.name} - ${rowInfo.terminalInfo.ipAddress}`),
    ).toBeInTheDocument();
    expect(screen.getByText(rowInfo.terminalInfo.macAddress)).toBeInTheDocument();
    expect(
      screen.getByText(
        `${moment(rowInfo.transactionDates.lastTransactionAt).format('DD/MM/YYYY')}`,
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(`${moment(rowInfo.dates.updatedAt).format('DD/MM/YYYY')}`),
    ).toBeInTheDocument();
    expect(screen.getByText(rowInfo.terminalInfo.version)).toBeInTheDocument();
    expect(screen.getByText('32')).toBeInTheDocument();
    const statusToggleCells = screen.getAllByRole('switch');
    expect(statusToggleCells[0]).toBeInTheDocument();
    fireEvent.click(statusToggleCells[0]);
    expect(onToggleStatus).toHaveBeenCalledWith({ id: rowInfo.id, isActive: !rowInfo.isActive });

    // Status toggle button should be disabled when 'Digital Billing' product is delinked for that Store
    expect(statusToggleCells[2]).toBeDisabled();

    // Disabled toggle button tooltip validation
    const disabledToggle = screen.getByTestId('tooltip-interactive-wrapper');
    await userEvent.hover(disabledToggle);
    await waitFor(() => {
      expect(
        screen.getByText(
          'This terminal has been removed from the store and can no longer be activated or deactivated',
        ),
      ).toBeInTheDocument();
    });

    // Page number change
    fireEvent.click(screen.getByText(3));
    expect(changePage).toHaveBeenCalledWith(20);

    // Page size change
    const pageSizePicker = screen.getByRole('combobox');
    fireEvent.click(pageSizePicker);
    fireEvent.click(screen.getByRole('option', { name: '50' }));
    expect(changePageSize).toHaveBeenCalledWith(50);
  });

  test('should render placeholder content when no terminal records are available', () => {
    const props = {
      tableProps: {
        ...TERMINALS_TABLE_PROPS,
        terminalsData: [],
      },
    };

    render(<App props={props} />);

    // Terminals placeholder content
    expect(
      screen.getByText(
        'No Billing Terminals found. Add terminals to a store to see your terminals data here.',
      ),
    ).toBeInTheDocument();
  });

  test("should not render Alert modal on Status toggle change when sessionStorage hold 'storeTerminalStatusAlertAcknowledged' property", () => {
    const onToggleStatus = jest.fn();
    const props = {
      tableProps: {
        ...TERMINALS_TABLE_PROPS,
        terminalsData: [TERMINALS_DATA[1]],
      },
      onToggleStatus,
    };

    window.sessionStorage.setItem('storeTerminalStatusAlertAcknowledged', 'true');
    render(<App props={props} />);

    fireEvent.click(screen.getByRole('switch'));
    expect(
      screen.queryByText(
        "Turning off the terminal will stop generating digital bills on this store's terminal. Click OK to continue",
      ),
    ).not.toBeInTheDocument();
    expect(onToggleStatus).toHaveBeenCalledWith({ id: 'NYJrOwFmMa5r90', isActive: false });
  });
});
