import React, { useCallback, useEffect } from 'react';
import Input from 'common/new-ui/Input';
import { IconButton, Link, TrashIcon } from '@razorpay/blade/components';
import { connect } from 'react-redux';

const SlabsHeader = ({ label, className }) => (
  <div
    className={`font-bold font-12 slabs-input slabs-input-header${
      className ? ` ${className}` : ''
    }`}
  >
    {label}
  </div>
);

function RateSlabs({ slabs, updateSlabs, updateDisabled, configs, editMode }) {
  const addMoreSlabs = useCallback(() => {
    const { lte } = slabs[slabs.length - 1];
    if (lte > 0) {
      const newSlabs = [...slabs];
      newSlabs.push({
        gte: parseInt(lte, 10) + 1,
        lte: '',
        fee: 0,
        error: {
          lte: '',
          gte: '',
          fee: '',
        },
      });
      updateSlabs(newSlabs);
    }
  }, [slabs, updateSlabs]);

  const removeSlab = useCallback(
    (e) => {
      const { arrInd } = e.currentTarget.dataset;
      const parsedArrInd = parseInt(arrInd, 10);
      const newSlabs = [...slabs];
      newSlabs.splice(parsedArrInd, 1);
      updateSlabs(newSlabs);
    },
    [slabs, updateSlabs],
  );

  const saveSlabs = useCallback(
    (newSlabs, arrInd) => {
      newSlabs[arrInd].error.lte = '';
      newSlabs[arrInd].error.gte = '';
      newSlabs[arrInd].error.fee = '';
      updateDisabled(false);
      updateSlabs(newSlabs);
    },
    [updateDisabled, updateSlabs],
  );

  // handles slab value change, finds the slab to be updated via arrInd data attribute, then based on key (lte, gte, fee) performs validations & update slabs state
  const handleSlabValueChange = useCallback(
    (e) => {
      let { value } = e.target;
      const {
        dataset: { key, arrInd },
      } = e.target;
      const parsedArrInd = parseInt(arrInd, 10);
      const nextInd = parsedArrInd + 1;
      value = isNaN(parseInt(value, 10)) ? value : parseInt(value, 10);
      const newSlabs = [...slabs];
      newSlabs[arrInd][key] = value;
      if (value < 0) {
        newSlabs[arrInd].error[key] = 'Invalid value';
        updateDisabled(true);
        updateSlabs(newSlabs);
        return;
      }
      // no need for validation of range & lte,gte for fee input
      if (key === 'fee') {
        saveSlabs(newSlabs, arrInd);
        return;
      }
      // check if slab in same range already exists
      const inRangeSlab = newSlabs.find(
        (s, idx) => parsedArrInd !== idx && value > s.gte && value <= s.lte,
      );
      if (inRangeSlab) {
        newSlabs[arrInd].error[key] = 'Slab in range exists';
        updateDisabled(true);
        updateSlabs(newSlabs);
        return;
      }
      if (key === 'lte') {
        // if next slab present (editing previous slab) - 0 to 100, 101 to 200, if slab with 100 is set as 100 then update next slab lower range to 102
        if (newSlabs[nextInd]) {
          if (newSlabs[nextInd].gte === value) {
            newSlabs[nextInd].gte += 1;
            if (value + 1 >= newSlabs[nextInd].lte) {
              newSlabs[nextInd].lte = '';
              newSlabs[nextInd].error.lte = 'Invalid value';
              updateDisabled(true);
              updateSlabs(newSlabs);
              return;
            }
          }
        }
        // if slab - 101 to 200 & user sets update 200 to 99 then handle error
        if (value <= newSlabs[arrInd].gte) {
          newSlabs[arrInd].error.lte = 'Invalid value';
          updateDisabled(true);
          newSlabs[arrInd].error.gte = '';
          updateSlabs(newSlabs);
          return;
        }
      } else if (key === 'gte') {
        // if slab - 101 to 200 & user sets update 101 to > 200 then handle error
        if (value >= newSlabs[arrInd].lte) {
          newSlabs[arrInd].error.lte = 'Invalid value';
          updateDisabled(true);
          newSlabs[arrInd].error.gte = '';
          updateSlabs(newSlabs);
          return;
        }
      }
      saveSlabs(newSlabs, arrInd);
    },
    [updateDisabled, slabs, updateSlabs, saveSlabs],
  );

  useEffect(() => {
    if (!slabs || slabs.length === 0) {
      updateSlabs([
        {
          gte: 0,
          lte: 0,
          fee: 0,
          error: {
            lte: '',
            gte: '',
            fee: '',
          },
        },
      ]);
      updateDisabled(true);
    }
  }, []);

  return (
    <>
      {slabs.map((item, index) => {
        return (
          <div key={index} className="display-flex slabs-form-wrapper">
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
            {configs.rate_slabs && (
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
            {!editMode && (
              <div data-arr-ind={index} onClick={removeSlab} className="delete-slab">
                <IconButton className="delete-slab" variant="primary" icon={TrashIcon} />
              </div>
            )}
          </div>
        );
      })}
      {!editMode && (
        <Link variant="button" onClick={addMoreSlabs}>
          + Add rate slabs
        </Link>
      )}
    </>
  );
}
const mapStateToProps = (state) => ({
  configs: state.magicCODEngine.configs,
});
export default connect(mapStateToProps, null)(RateSlabs);
