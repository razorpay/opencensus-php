import React, { useEffect, useState } from 'react';
import {
  Button,
  BottomSheet,
  BottomSheetHeader,
  TextInput,
  BottomSheetBody,
  DatePicker,
  SelectInput,
  Box,
  BottomSheetFooter,
  Dropdown,
  ActionList,
  ActionListItem,
  Text,
  FilterIcon,
  Link,
  RefreshIcon,
} from '@razorpay/blade/components';
import moment from 'moment';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { getCurrentYear } from 'common/utils/date-utils';
import {
  decodeSensitiveFields,
  encodeSensitiveFields,
  getURLQueryParams,
  stringifyQueryParams,
} from 'common/utils/rzp-utils';
import {
  TODAY,
  LAST_7_DAYS,
  LAST_30_DAYS,
  LAST_90_DAYS,
  CURRENT_YEAR_JAN_TILL_DATE,
  THIS_FINANCIAL_YEAR,
} from 'merchant/views/Transactions/v1/Payments/components/JKBankTransactions/constants';
import { getFromDate } from 'merchant/views/Transactions/v1/Payments/components/JKBankTransactions/helper';
import { DropdownWrapper } from 'merchant/views/Transactions/v1/Payments/components/JKBankTransactions/styled';

interface MobileFilterProps {
  onSubmit: (state: FormStateType) => void;
  location: any;
  history: any;
}

interface FormStateType {
  from: string | number;
  to: string | number;
  status: string;
  contact: string;
  id: string;
  rrn: string;
  va_transaction_id: string;
}

type selectionType = 'single';

const MobileFilterContainer = ({ onSubmit, ...props }: MobileFilterProps) => {
  const [isBottomSheetOpen, setIsBottomSheetOpen] = useState(false);
  const [formState, setFormState] = useState<FormStateType>({
    from: '',
    to: '',
    status: '',
    contact: '',
    id: '',
    rrn: '',
    va_transaction_id: '',
  });

  const initForm = () => {
    let params = {};
    if (props.location.search) {
      params = getURLQueryParams(props.location.search);
    }

    for (const key in params) {
      if (params.hasOwnProperty(key)) {
        params[key] = decodeURI(params[key]);
      }
    }

    params = decodeSensitiveFields(params);
    setFormState(params as FormStateType);
  };

  useEffect(() => {
    initForm();
  }, []);

  const handleFormSubmit = () => {
    const { status, contact, id, to, from, rrn, va_transaction_id } = formState;

    let params = {
      status,
      contact,
      id,
      to,
      from,
      rrn,
      va_transaction_id,
    };

    const parsedParams = Object.fromEntries(
      Object.entries(params).filter(([_, value]) => value !== undefined),
    );

    params = encodeSensitiveFields(parsedParams);

    const {
      history,
      location: { pathname, hash, state },
    } = props;

    const queryParamsProps = { ...params };

    const historyObject = {
      pathname,
      hash,
      state,
      search: stringifyQueryParams(queryParamsProps),
    };

    history.push(historyObject);

    setIsBottomSheetOpen(false);

    onSubmit(params as FormStateType);
  };

  const handleFormChange = ({ name, value }) => {
    setFormState((prevState) => ({ ...prevState, [name]: value }));
  };

  const endDate = moment().endOf('day').toDate();

  return (
    <>
      <>
        <DatePicker
          label={
            {
              end: 'To',
              start: 'From',
            } as unknown as string
          }
          value={[
            formState.from ? moment.unix(formState.from as unknown as number).toDate() : '',
            formState.to ? moment.unix(formState.to as unknown as number).toDate() : '',
          ]}
          onChange={(e) => {
            const [from, to] = e;
            setFormState((prevState) => ({
              ...prevState,
              from: from ? moment(from).unix() : '',
              to: to ? moment(to).endOf('day').unix() : '',
            }));
          }}
          onApply={() => {
            handleFormSubmit();
          }}
          selectionType={'range' as selectionType}
          presets={[
            {
              label: 'Today',
              value: () => [getFromDate(TODAY), moment().toDate()],
            },
            {
              label: 'Last 7 days',
              value: () => [getFromDate(LAST_7_DAYS), endDate],
            },
            {
              label: 'Last 30 days',
              value: () => [getFromDate(LAST_30_DAYS), endDate],
            },
            {
              label: 'Last 90 days',
              value: () => [getFromDate(LAST_90_DAYS), endDate],
            },
            {
              label: 'This Financial Year',
              value: () => [getFromDate(THIS_FINANCIAL_YEAR), endDate],
            },
            {
              label: `Jan ${getCurrentYear()} - till date`,
              value: () => [getFromDate(CURRENT_YEAR_JAN_TILL_DATE), endDate],
            },
          ]}
          defaultValue={undefined}
          onMonthSelect={undefined}
          onYearSelect={undefined}
        />
        <Box
          marginTop="spacing.7"
          display="flex"
          justifyItems="center"
          justifyContent="space-between"
        >
          <Box display="flex" alignItems="center">
            <Text weight="semibold" size="medium" marginRight="spacing.3">
              All transactions
            </Text>
            <Link
              testID="refresh-btn"
              icon={RefreshIcon}
              variant="button"
              onClick={() => {
                handleFormSubmit();
              }}
            />
          </Box>
          <Link
            onClick={() => {
              setIsBottomSheetOpen(true);
            }}
            icon={FilterIcon}
            variant="button"
            color="primary"
            size="large"
            iconPosition="left"
          >
            Filters
          </Link>
        </Box>
      </>
      <BottomSheet
        isOpen={isBottomSheetOpen}
        onDismiss={() => {
          setIsBottomSheetOpen(false);
        }}
        snapPoints={[0.8, 0.8, 1]}
      >
        <BottomSheetHeader title="Filters" />
        <BottomSheetBody padding="spacing.0">
          <Box
            display="flex"
            flexDirection="column"
            paddingY="spacing.3"
            paddingX="spacing.5"
            gap="spacing.5"
          >
            <TextInput
              onChange={(e) => {
                handleFormChange({
                  name: e.name,
                  value: e.value || '',
                });
              }}
              value={formState.id}
              name="id"
              label="Payment Id"
              placeholder="PaymentId"
            />
            <TextInput
              value={formState.rrn}
              onChange={(e) => {
                handleFormChange({
                  name: e.name,
                  value: e.value || '',
                });
              }}
              name="rrn"
              label="Payment Reference Number"
              placeholder="Payment Reference Number"
            />
            <DropdownWrapper>
              <Dropdown selectionType="single">
                <SelectInput
                  onChange={(e) => {
                    handleFormChange({
                      name: e.name,
                      value: e.values[0] || '',
                    });
                  }}
                  name="status"
                  value={formState.status}
                  placeholder="Select"
                  label="Status"
                />
                <BottomSheet>
                  <BottomSheetHeader showBackButton={true} title="Status" />
                  <BottomSheetBody padding="spacing.0">
                    <Box padding="spacing.7">
                      <ActionList>
                        <ActionListItem title="All" value="" />
                        <ActionListItem title="Authorized" value="authorized" />
                        <ActionListItem title="Captured" value="captured" />
                        <ActionListItem title="Failed" value="failed" />
                        <ActionListItem title="Redunded" value="refunded" />
                      </ActionList>
                    </Box>
                  </BottomSheetBody>
                </BottomSheet>
              </Dropdown>
            </DropdownWrapper>

            <TextInput
              name="va_transaction_id"
              label="Bank Reference Number"
              placeholder="Bank Reference Number"
              onChange={(e) => {
                handleFormChange({
                  name: e.name,
                  value: e.value || '',
                });
              }}
              value={formState.va_transaction_id}
            />
          </Box>
        </BottomSheetBody>
        <BottomSheetFooter>
          <Box display="flex" justifyContent="space-between">
            <Button
              onClick={() => {
                setIsBottomSheetOpen(false);
              }}
              marginRight="spacing.5"
              isFullWidth={true}
              variant="tertiary"
            >
              Cancel
            </Button>
            <Button
              onClick={() => {
                handleFormSubmit();
              }}
              isFullWidth={true}
            >
              Apply
            </Button>
          </Box>
        </BottomSheetFooter>
      </BottomSheet>
    </>
  );
};

export default compose(
  connect((state) => ({
    user: state.session.user,
  })),
)(withRouter(MobileFilterContainer));
