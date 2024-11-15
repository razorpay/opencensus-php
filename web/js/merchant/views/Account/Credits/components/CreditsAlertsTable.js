import { forwardRef, useState, useImperativeHandle } from 'react';
import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  TableFooter,
  TableFooterRow,
  TableFooterCell,
  Button,
  Box,
} from '@razorpay/blade/components';
import { titleCase, paiseToRupees, rupeesToPaise } from 'common/utils/rzp-utils';
import Input from 'common/new-ui/Input';
import { getCurrency } from 'common/ui/Amount';
import { isInteger } from 'common/utils/validators';

const AlertRow = ({ creditType, alertValue, onChangeHandler, index }) => {
  const setThresholdValues = (value, threshold) => (!isInteger(value) ? 0 : `${value / threshold}`);
  const currencySymbol = getCurrency('INR').symbol;

  return (
    <TableRow item={{ creditType, alertValue }}>
      <TableCell>{creditType}</TableCell>
      <TableCell>
        <Input
          value={`${alertValue}`}
          onChange={(e) => {
            onChangeHandler(e, creditType);
          }}
          autoFocus={index === 0}
          mature
          validator={(val) => {
            if (val !== '' && !isInteger(val)) return 'Only numeric value allowed';
            return null;
          }}
          addonBefore={currencySymbol}
        />
      </TableCell>
      <TableCell>
        <Input disabled value={setThresholdValues(alertValue, 2)} addonBefore={currencySymbol} />
      </TableCell>
      <TableCell>
        <Input disabled value={setThresholdValues(alertValue, 4)} addonBefore={currencySymbol} />
      </TableCell>
    </TableRow>
  );
};

const CreditsAlertsTable = ({ items, columnNames, onClickCancel, onClickSave }, ref) => {
  const [alertValues, setalertValues] = useState(() => {
    return items.map((item) => {
      return {
        type: item.credit_type,
        alertValue: item.credit_alert_threshold ? paiseToRupees(item.credit_alert_threshold) : '',
      };
    });
  });

  const onChangeHandler = (e, creditType) => {
    let value = e.target.value;

    if (value === undefined) value = '';

    const updatedAlert = alertValues.filter((item) => item.type === creditType);
    updatedAlert[0].alertValue = value;

    const rest = alertValues.filter((item) => item.type !== creditType);

    setalertValues([...rest, ...updatedAlert]);
  };

  const isSaveDisabled = () => {
    const count = alertValues.reduce((acc, thresoldItem) => {
      if (thresoldItem.alertValue !== '' && !isInteger(thresoldItem.alertValue)) return acc + 1;
      else return acc;
    }, 0);

    return count > 0;
  };

  const getAlertValues = () =>
    alertValues.map((item) => {
      return {
        credit_type: item.type,
        credit_alert_threshold:
          item.alertValue !== '' ? rupeesToPaise(parseInt(item.alertValue, 10)) : 0,
      };
    });

  useImperativeHandle(ref, () => {
    return {
      getAlertValues,
    };
  });

  return (
    <Table data={{ nodes: items }}>
      {(tableData) => (
        <>
          <TableHeader>
            <TableHeaderRow>
              {columnNames.map((column, idx) => (
                <TableHeaderCell key={idx}>{titleCase(column)}</TableHeaderCell>
              ))}
            </TableHeaderRow>
          </TableHeader>
          <TableBody>
            {tableData.map((row, index) => (
              <AlertRow
                key={`alert_${index}`}
                index={index}
                creditType={row.credit_type}
                alertValue={
                  alertValues.filter((alert) => alert.type === row.credit_type)[0].alertValue
                }
                onChangeHandler={onChangeHandler}
              />
            ))}
          </TableBody>
          <TableFooter>
            <TableFooterRow>
              <TableFooterCell>
                <Box width="100%" display="flex" gap="spacing.6" justifyContent="flex-end">
                  <Button variant="tertiary" onClick={onClickCancel}>
                    Cancel
                  </Button>
                  <Button variant="primary" disabled={isSaveDisabled()} onClick={onClickSave}>
                    Save
                  </Button>
                </Box>
              </TableFooterCell>
            </TableFooterRow>
          </TableFooter>
        </>
      )}
    </Table>
  );
};

export default forwardRef(CreditsAlertsTable);
