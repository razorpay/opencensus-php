import React, { ReactElement, ReactNode, ReactText, useState } from 'react';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import View from '@razorpay/blade/src/atoms/View';
import Text from '@razorpay/blade/src/atoms/Text';
import Space from '@razorpay/blade/src/atoms/Space';
import Icon from '@razorpay/blade/src/atoms/Icon';
import Flex from '@razorpay/blade/src/atoms/Flex';
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
  onChange,
  onInputChange,
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

  const selctedLabel = selectedOption && selectedOption.props.label;
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

  const getOptions = (optionNodes: ReactElement[]) => {
    return optionNodes
      .filter((child: ReactElement<OptionsPropsT>) => {
        if (!searchable || !filterOptions || !inputValue) {
          return true;
        }
        return child.props.label.includes(inputValue);
      })
      .map((child: ReactElement<OptionsPropsT>, index) => {
        return (
          <Flex key={index} flexDirection="row">
            <Space margin={[0, 0, 2, 0]}>
              <OptionContainer $disabled={child.props.disabled} onClick={() => onSelect(child)}>
                <Text css={{ cursor: 'pointer' }} size="medium" color="shade.980">
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
        />
      </View>
      <Modal isOpen={isModalOpen} onClose={onModalClose} bottomsheet={true}>
        <ModalBody>
          <Space margin={[0, 0, 2, 0]}>
            <Text color="shade.960">{placeholder}</Text>
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
