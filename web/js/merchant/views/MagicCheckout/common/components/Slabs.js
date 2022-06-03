import Input from 'common/new-ui/Input';
import { useCallback, useState } from 'react';
import { FEE_RULES } from 'merchant/views/MagicCheckout/constants';
import validators from 'merchant/views/MagicCheckout/common/feeUtils';

const Slabs = ({ type, slabs, updateSlabs }) => {
  const heading = type === FEE_RULES.COD_FEE_RULE ? 'COD' : 'Shipping';
  const [slabsClass, setSlabsClass] = useState('slabs-close');
  if (!slabs || slabs.length === 0) {
    slabs = [{ gte: 0, lte: 0, fee: 0 }];
  }

  const handleBlur = (index, val) => {
    setSlabsClass(
      validators.lte(slabs, index)(val) === '' ? ' slabs-close' : ' slabs-close-errored',
    );
  };
  const addMoreSlabs = useCallback(() => {
    const { lte } = slabs[slabs.length - 1];
    if (lte > 0) {
      const newSlabs = [...slabs];
      newSlabs.push({
        gte: parseInt(lte, 10) + 1,
        lte: '',
        fee: 0,
      });
      updateSlabs(newSlabs);
    }
  }, [slabs, updateSlabs]);

  const removeSlab = useCallback(
    (e) => {
      const { arrInd } = e.target.dataset;
      const parsedArrInd = parseInt(arrInd, 10);
      const newSlabs = [...slabs];
      newSlabs.splice(parsedArrInd, 1);
      updateSlabs(newSlabs);
    },
    [slabs, updateSlabs],
  );

  const SlabsHeader = ({ label, className }) => (
    <div
      className={`font-bold font-12 slabs-input slabs-input-header${
        className ? ` ${className}` : ''
      }`}
    >
      {label}
    </div>
  );

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
      if (newSlabs[nextInd] && key === 'lte') {
        newSlabs[nextInd].gte = value + 1;
        if (newSlabs[nextInd].lte < value + 1) {
          newSlabs[nextInd].lte = '';
        }
      }
      updateSlabs(newSlabs);
    },
    [slabs, updateSlabs],
  );

  return (
    <>
      <div className="display-flex slabs-input-container">
        <SlabsHeader label="Min Order Value" />
        <SlabsHeader label="Max Order Value" />
        <SlabsHeader label={`${heading} Charge`} className="slabs-charge" />
      </div>
      {slabs.map((item, index) => {
        return (
          <div key={index} className="display-flex slabs-form-wrapper">
            <div className="display-flex">
              <div className="slabs-input-container">
                <Input
                  addonBefore="₹"
                  className="slabs-input"
                  type="number"
                  value={item.gte}
                  data-key="gte"
                  data-arr-ind={index}
                  disabled={true}
                  onChange={handleSlabValueChange}
                />
              </div>
              <div className="slabs-input-container">
                <Input
                  addonBefore="₹"
                  type="number"
                  value={item.lte}
                  data-key="lte"
                  data-arr-ind={index}
                  className="slabs-input"
                  onChange={handleSlabValueChange}
                  onBlur={() => handleBlur(index, item.lte)}
                  validator={validators.lte(slabs, index)}
                />
              </div>
            </div>
            <div className="slabs-charge slabs-input-container">
              <Input
                addonBefore="₹"
                className="slabs-input"
                type="number"
                value={item.fee}
                data-key="fee"
                data-arr-ind={index}
                onChange={handleSlabValueChange}
              />
            </div>
            {index > 0 ? (
              <div
                className={`display-flex flex-center pointer${slabsClass}`}
                data-arr-ind={index}
                onClick={removeSlab}
              >
                {' '}
                X{' '}
              </div>
            ) : null}
          </div>
        );
      })}
      <div
        className="add-slabs-cta font-12 font-bold pointer display-inline"
        onClick={addMoreSlabs}
      >
        + Add More Slabs
      </div>
    </>
  );
};

export default Slabs;
