import React, { useCallback, useEffect, useRef } from 'react';
import Input from 'common/new-ui/Input';
import { Link } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { COD_ENGINES, MAX_FEE_RULES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import { isBasicCODEngine } from 'merchant/views/MagicCheckout/CODSettings/utils';

const SlabsHeader = ({ label, className }) => (
  <div
    className={`font-bold font-12 slabs-input slabs-input-header${
      className ? ` ${className}` : ''
    }`}
  >
    {label}
  </div>
);

function RateSlabs({ slabs, updateSlabs, configs, editMode, isRCOD }) {
  const hasRates = configs.rate_slabs || (configs.engine === COD_ENGINES.ADVANCED && !isRCOD);
  const addSlabButton = useRef(null);
  const addMoreSlabs = useCallback(() => {
    const { lte } = slabs[slabs.length - 1];
    if (lte > 0) {
      const newSlabs = [...slabs];
      const addSlab = {
        gte: parseInt(lte, 10) + 1,
        lte: '',
        fee: 0,
        error: {
          lte: '',
          gte: '',
          fee: '',
        },
      };
      if (isRCOD) {
        addSlab.name = '';
        addSlab.error.name = '';
      }
      newSlabs.push(addSlab);

      updateSlabs(newSlabs);
    }
  }, [slabs, updateSlabs, isRCOD]);
  useEffect(() => {
    addSlabButton?.current?.scrollIntoView({ behavior: 'smooth' });
  }, [slabs]);

  const saveSlabs = useCallback(
    (newSlabs, arrInd, key) => {
      newSlabs[arrInd].error[key] = '';
      updateSlabs(newSlabs);
    },
    [updateSlabs],
  );

  // handles slab value change, finds the slab to be updated via arrInd data attribute, then based on key (lte, gte, fee) performs validations & update slabs state
  const handleSlabValueChange = (e) => {
    let { value } = e.target;
    const {
      dataset: { key, arrInd },
    } = e.target;
    const parsedArrInd = parseInt(arrInd, 10);
    const nextInd = parsedArrInd + 1;
    if (key !== 'name') {
      value = isNaN(parseInt(value, 10)) ? '' : parseInt(value, 10);
    }
    const newSlabs = [...slabs];
    newSlabs[arrInd][key] = value;
    if (value < 0) {
      newSlabs[arrInd].error[key] = 'Invalid value';
      updateSlabs(newSlabs);
      return;
    }

    if (key === 'name') {
      value = value?.trim();
      if (!value) {
        newSlabs[arrInd].error[key] = 'Required';
        updateSlabs(newSlabs);
        return;
      }
      // no need for validation of range & lte,gte for name input
      saveSlabs(newSlabs, arrInd, key);
      return;
    }

    // no need for validation of range & lte,gte for fee
    if (key === 'fee') {
      saveSlabs(newSlabs, arrInd, key);
      return;
    }

    // check if slab in same range already exists
    const inRangeSlab = newSlabs.find(
      (s, idx) => parsedArrInd !== idx && value >= s.gte && value <= s.lte,
    );
    if (inRangeSlab && isBasicCODEngine(configs.engine) && !isRCOD) {
      newSlabs[arrInd].error[key] = 'Slab in range exists';
      updateSlabs(newSlabs);
      return;
    }
    if (key === 'lte') {
      // if next slab present (editing previous slab) - 0 to 100, 101 to 200, if slab with 100 is set as 100 then update next slab lower range to 102
      if (newSlabs[nextInd] && !isRCOD) {
        if (newSlabs[nextInd].gte === value) {
          newSlabs[nextInd].gte += 1;
          if (value + 1 >= newSlabs[nextInd].lte) {
            newSlabs[nextInd].lte = '';
            newSlabs[nextInd].error.lte = 'Invalid value';
            updateSlabs(newSlabs);
            return;
          }
        }
      }
      // if slab - 101 to 200 & user sets update 200 to 99 then handle error
      if (value <= newSlabs[arrInd].gte) {
        newSlabs[arrInd].error.lte = 'Invalid value';
        newSlabs[arrInd].error.gte = '';
        updateSlabs(newSlabs);
        return;
      }
    } else if (key === 'gte') {
      // if slab - 101 to 200 & user sets update 101 to > 200 then handle error
      if (value >= newSlabs[arrInd].lte) {
        newSlabs[arrInd].error.lte = 'Invalid value';
        newSlabs[arrInd].error.gte = '';
        updateSlabs(newSlabs);
        return;
      }
    }
    saveSlabs(newSlabs, arrInd, key);
  };

  useEffect(() => {
    if (!slabs || slabs.length === 0) {
      const initialVal = {
        gte: 0,
        lte: 0,
        fee: 0,
        error: {
          lte: 'Invalid value',
          gte: '',
          fee: '',
        },
      };

      if (isRCOD) {
        initialVal.name = '';
        initialVal.error.name = '';
      }

      updateSlabs([initialVal]);
    }
  }, []);

  return (
    <>
      {slabs.map((item, index) => {
        return (
          <div key={index} className="display-flex slabs-form-wrapper">
            {isRCOD ? (
              <div className="slabs-input-container">
                <SlabsHeader label="COD Method Name" />
                <Input
                  className="slabs-input"
                  type="text"
                  value={item.name || ''}
                  data-key="name"
                  data-arr-ind={index}
                  onChange={handleSlabValueChange}
                  propagatedError={item.error.name}
                />
              </div>
            ) : null}
            <div className="slabs-input-container">
              <SlabsHeader label="Min Order Value" />
              <Input
                addonBefore="₹"
                className="slabs-input"
                type="number"
                value={item.gte}
                data-key="gte"
                data-arr-ind={index}
                onChange={handleSlabValueChange}
                propagatedError={item.error.gte}
              />
            </div>
            <div className="slabs-input-container">
              <SlabsHeader label="Max Order Value" />
              <Input
                addonBefore="₹"
                type="number"
                value={item.lte}
                data-key="lte"
                data-arr-ind={index}
                className="slabs-input"
                onChange={handleSlabValueChange}
                propagatedError={item.error.lte}
              />
            </div>
            {hasRates && (
              <div className="slabs-charge slabs-input-container">
                <SlabsHeader label="Delivery price" className="slabs-charge" />
                <Input
                  addonBefore="₹"
                  className="slabs-input"
                  type="number"
                  value={item.fee}
                  data-key="fee"
                  data-arr-ind={index}
                  onChange={handleSlabValueChange}
                  propagatedError={item.error.fee}
                />
              </div>
            )}
          </div>
        );
      })}
      {!editMode && slabs.length <= MAX_FEE_RULES ? (
        <div ref={addSlabButton}>
          <Link variant="button" onClick={addMoreSlabs}>
            + Add rate slabs
          </Link>
        </div>
      ) : null}
    </>
  );
}
const mapStateToProps = (state) => ({
  configs: state.magicCODEngine.configs,
  isRCOD: state.magic_settings.rcodEnabled,
});
export default connect(mapStateToProps, null)(RateSlabs);
