import { render, screen, waitFor, userEvent } from 'test-utils';
import {
  getAllOptions,
  getDropdownTarget,
  getDropdownContent,
} from 'common/components/Dropdown/utils';
import { Dropdown as BladeDropdown } from '@razorpay/blade/components';
import { utils } from './mocks/fixtures';

describe('Dropdown Utils', () => {
  describe('getAllOptions', () => {
    test('should return an array of all options', () => {
      const options = utils.getAllOptions.options;
      const expectedAllOptions = utils.getAllOptions.expectedAllOptions;
      const allOptions = getAllOptions(options);
      expect(allOptions).toEqual(expectedAllOptions);
    });
  });

  describe('getDropdownTarget', () => {
    const defaultProps = utils.getDropdownTarget.defaultProps;
    const renderApp = (props = {}) => {
      render(
        getDropdownTarget({
          ...defaultProps,
          ...props,
        }),
      );
    };
    test('should render a Dropdown title', () => {
      renderApp();
      expect(screen.getByText(defaultProps.dropdownTitle)).toBeInTheDocument();
    });

    test('should render a link when  is true', () => {
      renderApp({
        isLink: true,
      });
      expect(screen.getByRole('button')).toHaveAttribute('data-blade-component', 'link');
    });

    test('should render a SelectInput when isSelectInput is true', () => {
      renderApp({
        isLink: false,
        isSelectInput: true,
        selectInputName: 'selectInput',
        defaultOptions: [{ label: 'Option 1', value: 'option1' }],
      });
      expect(screen.getByRole('combobox')).toBeInTheDocument();
    });

    test('should render a DropdownButton when isSelectInput and isLink is false', () => {
      renderApp({
        isLink: false,
        isSelectInput: false,
        selectInputName: 'selectInput',
      });
      expect(
        screen.getByRole('button', {
          name: defaultProps.dropdownTitle,
        }),
      ).toBeInTheDocument();
    });
  });

  describe('getDropdownContent', () => {
    const defaultProps = utils.getDropdownContent;
    const renderApp = async (props = {}) => {
      const dropdownTargetDefaultProps = utils.getDropdownTarget.defaultProps;
      render(
        <BladeDropdown>
          {getDropdownTarget({
            ...dropdownTargetDefaultProps,
          })}
          {getDropdownContent({
            ...defaultProps,
            ...props,
          })}
        </BladeDropdown>,
      );
      const dropdownTrigger = screen.getByRole('button', { name: 'Dropdown Title' });
      expect(dropdownTrigger).toBeInTheDocument();
      await userEvent.click(dropdownTrigger);
    };

    test('should render an selection menu when isWithBottomSheet is false', async () => {
      await renderApp({
        isWithBottomSheet: false,
        isMultipleSelection: false,
      });
      await waitFor(() => expect(screen.getByRole('menu')).toBeInTheDocument());
    });

    test('should render a FooterActions when isMultipleSelection is true', async () => {
      await renderApp({
        isWithBottomSheet: false,
        isMultipleSelection: true,
      });
      await waitFor(() =>
        expect(screen.getByRole('button', { name: 'Clear' })).toBeInTheDocument(),
      );
      expect(screen.getByRole('button', { name: 'Apply' })).toBeInTheDocument();
    });
  });
});
