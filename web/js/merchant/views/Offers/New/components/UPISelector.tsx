import React, { useEffect } from 'react';
import {
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Box,
  DropdownFooter,
  Button,
} from '@razorpay/blade/components';
import { UPI_APP_PROVIDERS } from '../../constants';
import { COMMON_Z_INDEX } from 'common/constant';

interface Props {
  onChangeHandler: Function;
  selectedValues: string[];
  errorText: string;
}

export const UPI_APPS_SELECT_OPTIONS = [
  { label: 'All UPI Apps', name: UPI_APP_PROVIDERS.ALL },
  { label: 'Google Pay', name: UPI_APP_PROVIDERS.GPAY },
  { label: 'Phone Pe', name: UPI_APP_PROVIDERS.PHONEPE },
  { label: 'Paytm', name: UPI_APP_PROVIDERS.PAYTM },
  { label: 'Amazon Pay', name: UPI_APP_PROVIDERS.AMAZONPAY },
  { label: 'Cred', name: UPI_APP_PROVIDERS.CRED },
];

const UPISelector: React.FC<Props> = React.memo(
  ({ onChangeHandler, selectedValues, errorText }) => {
    const [selected, setSelected] = React.useState<string[]>([]);
    const [isOpen, setOpenDropdown] = React.useState<boolean>(false);
    // Fix: Dropdown controlled state hack
    const isDropdownOpenRef = React.useRef<boolean>(false);
    const selectedPropRef = React.useRef<string[]>(selectedValues);

    useEffect(() => {
      setSelected(selectedValues);
      selectedPropRef.current = selectedValues;
    }, [selectedValues]);

    function onSave() {
      isDropdownOpenRef.current = false;
      setOpenDropdown(false);
      // onDropdownToggle(false, true)
      //Fix: Trigger onOpenChange by simulating click
      // document.querySelector('.side-bar-container')?.click();
      onChangeHandler('upiAppsList', selected);
    }

    function onClear() {
      setSelected([]);
    }

    function onDropdownToggle(nextIsOpen: boolean) {
      if (nextIsOpen == isDropdownOpenRef.current) return;
      console.log(isDropdownOpenRef.current);
      // Fix: isOpen and onOpenChange doesn't sync.
      // Clear the dropdown only if onSave wasn't triggered before
      if (!nextIsOpen) {
        setSelected(selectedPropRef.current);
      }
      isDropdownOpenRef.current = nextIsOpen;
      setOpenDropdown(nextIsOpen);
    }

    function onChange({ values }) {
      setSelected(values);

      if (isDropdownOpenRef.current) return;

      onChangeHandler('upiAppsList', values);
    }

    return (
      <Dropdown
        selectionType="multiple"
        onOpenChange={onDropdownToggle}
        isOpen={isOpen}
        marginTop="spacing.4"
      >
        <SelectInput
          labelPosition="left"
          label="Selected Apps"
          name="upiAppsList"
          value={selected}
          onChange={onChange}
          placeholder="Select UPI Apps"
          isRequired
          necessityIndicator="required"
          validationState={errorText.length ? 'error' : 'none'}
          errorText={errorText}
          testID="upi-selector"
        />
        <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
          <ActionList>
            {UPI_APPS_SELECT_OPTIONS.slice(1).map((opt) => (
              <ActionListItem title={opt.label} value={opt.name} key={opt.name} />
            ))}
          </ActionList>
          <DropdownFooter>
            <Box display="flex" alignItems="center">
              <Box flex="1">
                <Button
                  isFullWidth
                  isDisabled={selected.length === 0}
                  onClick={onClear}
                  variant="tertiary"
                >
                  Clear
                </Button>
              </Box>
              <Box width="10px" />
              <Box flex="1">
                <Button isFullWidth onClick={onSave}>
                  Save
                </Button>
              </Box>
            </Box>
          </DropdownFooter>
        </DropdownOverlay>
      </Dropdown>
    );
  },
);

export default UPISelector;
