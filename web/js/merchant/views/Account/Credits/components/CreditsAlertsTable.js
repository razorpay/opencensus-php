import { forwardRef, useState, useImperativeHandle } from 'react';
import TableBody from 'common/ui/TableBody';
import { titleCase, paiseToRupees, rupeesToPaise } from 'common/utils/rzp-utils';
import Input from 'common/new-ui/Input';
import { getCurrency } from 'common/ui/Amount';
import { isInteger } from 'common/utils/validators';

const AlertRow = ({ creditType, alertValue, onChangeHandler, index }) => {
  const setThresholdValues = (value, threshold) => (!isInteger(value) ? 0 : `${value / threshold}`);
  const currencySymbol = getCurrency('INR').symbol;

  return (
    <tr class="alert-row">
      <td>{creditType}</td>
      <td>
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
      </td>
      <td>
        <Input disabled value={setThresholdValues(alertValue, 2)} addonBefore={currencySymbol} />
      </td>
      <td>
        <Input disabled value={setThresholdValues(alertValue, 4)} addonBefore={currencySymbol} />
      </td>
    </tr>
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
        credit_alert_threshold: rupeesToPaise(parseInt(item.alertValue, 10)),
      };
    });

  useImperativeHandle(ref, () => {
    return {
      getAlertValues,
    };
  });

  return (
    <>
      <div class="table-reponsive">
        <table class="table table-hover table-striped">
          <thead>
            <tr>
              {columnNames.map((column, idx) => {
                return <th key={idx}>{titleCase(column)}</th>;
              })}
            </tr>
          </thead>
          <TableBody colSpan={4} rows={items}>
            {items.map((row, index) => {
              return (
                <AlertRow
                  key={`alert_${index}`}
                  index={index}
                  creditType={row.credit_type}
                  alertValue={
                    alertValues.filter((alert) => alert.type === row.credit_type)[0].alertValue
                  }
                  onChangeHandler={onChangeHandler}
                />
              );
            })}
          </TableBody>
        </table>
      </div>
      <div class="footer">
        <button class="btn btn-default" onClick={onClickCancel}>
          Cancel
        </button>
        <button class="btn btn-primary" disabled={isSaveDisabled()} onClick={onClickSave}>
          Save
        </button>
      </div>
    </>
  );
};

export default forwardRef(CreditsAlertsTable);
