import React from 'react';
import { render, screen, userEvent, waitFor } from '@dashboard/shared-ui/services/test/test-utils';
import Dropdown from '@dashboard/shared-ui/components/Dropdown';
import { defaultOptions, option1, option2, options } from './mocks/fixtures';

describe.skip('Dropdown', () => {
  const onChange = jest.fn();
  const defaultProps = {
    options,
    onChange,
  };

  const renderApp = async (props = {}, openDrodown = true) => {
    render(<Dropdown {...defaultProps} {...props} />);
    if (openDrodown) {
      const dropdownTrigger = screen.getByRole('button', { name: 'None' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
    }
  };

  beforeEach(() => {
    onChange.mockClear();
  });

  test('should render the menuitem and default options', async () => {
    await renderApp(
      {
        withBottomSheet: true,
        defaultOptions,
      },
      false,
    );
    const menutItems = screen.getAllByRole('menuitem');
    expect(menutItems).toHaveLength(options.length);
  });

  test('should calls the onChange function when an option is clicked', async () => {
    await renderApp();
    await userEvent.click(screen.getByRole('menuitem', { name: option1.title }));
    await waitFor(() => {
      expect(onChange).toHaveBeenCalledTimes(1);
      expect(onChange).toHaveBeenCalledWith([option1]);
    });
  });

  test('should be able to select multiple options', async () => {
    await renderApp({
      selectionType: 'multiple',
    });

    const firstOption = screen.getByRole('menuitem', { name: option1.title });
    const secondOption = screen.getByRole('menuitem', { name: option2.title });

    // initially should not selected
    expect(firstOption).toHaveAttribute('aria-selected', 'false');
    expect(secondOption).toHaveAttribute('aria-selected', 'false');

    await userEvent.click(firstOption);
    await userEvent.click(secondOption);

    // after click, should be selected
    expect(firstOption).toHaveAttribute('aria-selected', 'true');
    expect(secondOption).toHaveAttribute('aria-selected', 'true');
  });

  test('should clears the selected options on clear click', async () => {
    await renderApp({
      selectionType: 'multiple',
    });

    const firstOption = screen.getByRole('menuitem', { name: option1.title });
    const secondOption = screen.getByRole('menuitem', { name: option2.title });

    await userEvent.click(firstOption);
    await userEvent.click(secondOption);

    expect(firstOption).toHaveAttribute('aria-selected', 'true');
    expect(secondOption).toHaveAttribute('aria-selected', 'true');

    const clearButton = screen.getByRole('button', { name: 'Clear' });
    await userEvent.click(clearButton);

    expect(firstOption).toHaveAttribute('aria-selected', 'false');
    expect(secondOption).toHaveAttribute('aria-selected', 'false');
  });

  test('should select option on apply click', async () => {
    await renderApp({
      selectionType: 'multiple',
    });

    const secondOption = screen.getByRole('menuitem', { name: option2.title });
    await userEvent.click(secondOption);

    const applyButton = screen.getByRole('button', { name: 'Apply' });
    await userEvent.click(applyButton);

    await waitFor(() => {
      expect(onChange).toHaveBeenCalledTimes(1);
      expect(onChange).toHaveBeenCalledWith([option2]);
    });
  });

  test('should deselect option on re-click', async () => {
    await renderApp({
      selectionType: 'multiple',
    });
    // initially not selected
    const secondOption = screen.getByRole('menuitem', { name: option2.title });
    expect(secondOption).toHaveAttribute('aria-selected', 'false');

    // gets selected on click
    await userEvent.click(secondOption);
    expect(secondOption).toHaveAttribute('aria-selected', 'true');

    // gets not selected on clicking again
    await userEvent.click(secondOption);
    expect(secondOption).toHaveAttribute('aria-selected', 'false');
  });

  test('should select and deselect all options on all click', async () => {
    const optionsWithAll = [{ title: 'all', value: 'all' }, ...options];
    await renderApp({
      selectionType: 'multiple',
      options: optionsWithAll,
    });

    const allOption = screen.getByRole('menuitem', { name: 'all' });
    await userEvent.click(allOption);
    for (const { title } of options) {
      const option = screen.getByRole('menuitem', { name: title });
      expect(option).toHaveAttribute('aria-selected', 'true');
    }

    await userEvent.click(allOption);
    for (const { title } of options) {
      const option = screen.getByRole('menuitem', { name: title });
      expect(option).toHaveAttribute('aria-selected', 'false');
    }
  });
});
