import React, { ReactElement, ReactNode, ReactText, useEffect, useState } from 'react';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { FormikErrors } from 'formik';
import { Modal, ModalBody } from '../Modal';
import toArray from '../../services/children/to-array';
import { StatelessAccordion, Panel } from '../Accordian';
import Loader from '../Loader';
import { OptionsPropsT } from './Option';
import { OptionCheckIcon, OptionContainer } from './Styled';

export interface SelectPropsT {
  label: string;
  searchable?: boolean;
  placeholder?: string;
  inputPlaceholder?: string;
  children: ReactNode;
  onInputChange?: (value: string) => void;
  errorText?: string | false | string[] | FormikErrors<any> | FormikErrors<any>[] | undefined;
  onChange?: (value: string, option?: ReactElement<OptionsPropsT>) => void;
  value?: string;
  disabled?: boolean;
  filterOptions?: boolean;
  loading?: boolean;
  helpText?: string;
  bottomSheetHeaderText?: string;
  onInputBlur?: (value: string, option?: ReactElement<OptionsPropsT>) => void;
}

const Select: React.FC<SelectPropsT> = ({
  label,
  placeholder = 'Select',
  inputPlaceholder = 'Search',
  children,
  value,
  disabled = false,
  searchable = false,
  filterOptions = true,
  loading = false,
  errorText,
  helpText,
  onChange,
  onInputChange,
  bottomSheetHeaderText,
  onInputBlur = () => {},
}) => {
  const [isModalOpen, setModalOpen] = useState(false);
  const [selectedValue, setSelectedValue] = useState(value);
  const [isSelectInputDisabled, setSelectInputDisabled] = useState(false);
  const nodes = toArray(children);
  const {
    type: { isSelectGrpOption },
  } = nodes[0]
    ? (nodes[0] as ReactElement & { type: { isSelectGrpOption?: boolean } })
    : { type: { isSelectGrpOption: false } };
  let selectedOption: ReactElement<OptionsPropsT> | null = null;
  if (isSelectGrpOption) {
    nodes.forEach((child) => {
      toArray(child.props.children).forEach((optionChild) => {
        if (optionChild.props.value === selectedValue) {
          selectedOption = optionChild;
        }
      });
    });
  } else {
    selectedOption = nodes.filter((child) => child.props.value === selectedValue)[0];
  }

  let selctedLabel = selectedOption && selectedOption.props.label;
  selctedLabel = value === '' ? '' : selctedLabel;

  const [inputValue, setInputValue] = useState(selctedLabel);
  const [expanded, setExpanded] = useState<ReactText[]>(['0']);

  const handleInputChange = (val: string) => {
    setInputValue(val);
    if (onInputChange) {
      onInputChange(val);
    }
  };
  const onModalClose = () => {
    setModalOpen(false);
    setSelectInputDisabled(false);
  };

  const onSelect = (child: ReactElement<OptionsPropsT>) => {
    if (child.props.disabled) {
      return;
    }
    onModalClose();
    setSelectedValue(child.props.value);
    setInputValue(child.props.label);
    if (onChange) {
      onChange(child.props.value, child);
    }
  };

  useEffect(() => {
    setSelectedValue(value);
  }, [value]);

  const getOptions = (optionNodes: ReactElement[]) => {
    return optionNodes
      .filter((child: ReactElement<OptionsPropsT>) => {
        if (!searchable || !filterOptions || !inputValue) {
          return true;
        }
        return child.props.label.toLocaleLowerCase().includes(inputValue.toLocaleLowerCase());
      })
      .map((child: ReactElement<OptionsPropsT>, index) => {
        return (
          <Flex key={index} flexDirection="row">
            <Space margin={[0, 0, 2, 0]}>
              <OptionContainer $disabled={child.props.disabled} onClick={() => onSelect(child)}>
                <Text css={{ cursor: 'pointer', width: '100%' }} size="medium" color="shade.980">
                  {child}
                </Text>
                {child.props.value === selectedValue ? (
                  <Flex>
                    <OptionCheckIcon>
                      <Icon fill="primary.800" name="check" />
                    </OptionCheckIcon>
                  </Flex>
                ) : null}
              </OptionContainer>
            </Space>
          </Flex>
        );
      });
  };
  const getGroupedOptions = (grpNodes: ReactElement[]) => {
    return (
      <StatelessAccordion
        expanded={expanded}
        onChange={(_, exp) => setExpanded(exp)}
        accordian={true}
      >
        {grpNodes.map((child, index) => (
          <Panel key={String(index)} title={child.props.label}>
            {getOptions(toArray(child.props.children))}
          </Panel>
        ))}
      </StatelessAccordion>
    );
  };
  const getNodes = () => {
    if (loading) {
      return (
        <Flex flexDirection="row" justifyContent="center">
          <Space margin={[6, 2]}>
            <View>
              <Loader />
            </View>
          </Space>
        </Flex>
      );
    }
    if (nodes.length === 0) {
      return <Text align="center">No option to select</Text>;
    }
    return isSelectGrpOption ? getGroupedOptions(nodes) : getOptions(nodes);
  };
  return (
    <>
      <View
        onClick={() => {
          if (!disabled) {
            setModalOpen(true);
          }
          setSelectInputDisabled(true);
        }}
      >
        <TextInput
          width="auto"
          label={label}
          iconRight="chevronDown"
          placeholder={placeholder}
          variant="outlined"
          value={selctedLabel}
          disabled={disabled || isSelectInputDisabled}
          errorText={errorText}
          helpText={helpText}
          onBlur={onInputBlur}
        />
      </View>
      <Modal
        isOpen={isModalOpen}
        bottomSheetHeaderText={bottomSheetHeaderText}
        onClose={onModalClose}
        bottomsheet={true}
        bottomSheetHeight="90%"
      >
        <ModalBody>
          <Space margin={[0, 0, 2, 0]}>
            <Text weight="bold" size="xsmall" color="shade.960">
              {placeholder}
            </Text>
          </Space>
          {searchable ? (
            <Space margin={[0, 0, 2, 0]}>
              <View>
                <TextInput
                  width="auto"
                  label=""
                  iconRight="search"
                  placeholder={inputPlaceholder}
                  errorText=""
                  onChange={handleInputChange}
                  variant="filled"
                  value={inputValue}
                  onBlur={onInputBlur}
                />
              </View>
            </Space>
          ) : null}
          {getNodes()}
        </ModalBody>
      </Modal>
    </>
  );
};

export default Select;
