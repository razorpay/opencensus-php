import React, { useMemo } from 'react';
import { useFormikContext } from 'formik';

//Helper functions and constants
import { getIcon } from 'merchant/views/Settings/PaymentMethods/components/InstrumentIcons';
import { INSTRUMENTS } from './constants';
import { REQUESTABLE, GREYED } from 'merchant/views/Settings/PaymentMethods/constants';

//Components
import Input from 'common/new-ui/Input';

const InstrumentRow = ({ description, instrumentKey, instrumentList, instrumentsTat }) => {
  const { values, setFieldValue } = useFormikContext();

  const instrument = useMemo(() => {
    return instrumentList?.find(
      (instrument) => instrument?.name?.toLowerCase() === instrumentKey?.toLowerCase(),
    );
  }, [instrumentList]);
  const tat = instrumentsTat?.[`pg.international.${instrument?.slug}`];

  const handleChange = () => {
    const set = new Set(values[INSTRUMENTS]);
    if (set.has(instrumentKey)) {
      set.delete(instrumentKey);
    } else {
      set.add(instrumentKey);
    }
    setFieldValue(INSTRUMENTS, Array.from(set));
  };

  //This condition removes instruments with status other than requested
  if (![REQUESTABLE, GREYED].includes(instrument?.status)) return null;

  return (
    <label className="instrument-row">
      <Input.Check
        name="instruments"
        defaultValue={instrumentKey}
        checked={values[INSTRUMENTS].includes(instrumentKey)}
        onChange={handleChange}
        autoRender
      />
      <img src={getIcon(instrument?.icon)} />
      <div className="intrument-name detail-block">
        <h4>{instrument?.name}</h4>
        <p>{description}</p>
      </div>
      <div className="tat-wrapper detail-block">
        <h4>T+{tat ?? 20} days</h4>
        <p>Settlement TAT</p>
      </div>
      <div className="pricing-wrapper detail-block">
        <h4>3%</h4>
        <p>Pricing</p>
      </div>
    </label>
  );
};

export default InstrumentRow;
