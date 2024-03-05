import React from 'react';
import { render, screen, userEvent } from '@dashboard/shared-ui/services/test/test-utils';
import ActionListWrapper from '@dashboard/shared-ui/components/Dropdown/components/ActionListWrapper';
import {
  actionListWrapperOptions,
  option1,
  option2,
} from '@dashboard/shared-ui/components/Dropdown/__test__/mocks/fixtures';

describe('Dropdown - ActionListWrapper', () => {
  const options = actionListWrapperOptions;
  const selectedOptions = [option1];
  const tempSelectedOptions = [option2];
  const onOptionClick = jest.fn();

  const renderApp = (props = {}) => {
    render(
      <ActionListWrapper
        options={options}
        selectedOptions={selectedOptions}
        onOptionClick={onOptionClick}
        {...props}
      />,
    );
  };

  test('should renders the correct number of options', () => {
    renderApp();
    expect(screen.getAllByRole('menuitem')).toHaveLength(options.length);
  });

  test('should calls onOptionClick when an option is clicked', async () => {
    renderApp();
    await userEvent.click(screen.getByRole('menuitem', { name: options[0].title }));
    expect(onOptionClick).toHaveBeenCalledWith(options[0]);
  });

  test('should renders selected options as selected', () => {
    renderApp();
    expect(screen.getByRole('menuitem', { name: selectedOptions[0].title })).toHaveAttribute(
      'aria-selected',
      'true',
    );
  });

  test('should renders temp selected options as selected when it is multiple selection', () => {
    renderApp({
      isMultipleSelection: true,
      tempSelectedOptions,
    });
    expect(screen.getByRole('menuitem', { name: selectedOptions[0].title })).toHaveAttribute(
      'aria-selected',
      'false',
    );
    expect(screen.getByRole('menuitem', { name: tempSelectedOptions[0].title })).toHaveAttribute(
      'aria-selected',
      'true',
    );
  });
});
