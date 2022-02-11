import Input from 'common/new-ui/Input';
import { useCallback } from 'react';
import { FEE_RULES } from 'merchant/views/MagicCheckout/constants';

const Slabs = ({ type, slabs, updateSlabs, validationError = {}, removeError }) => {
  const { errorInd } = validationError;
  const heading = type === FEE_RULES.COD_FEE_RULE ? 'COD' : 'Shipping';
  if (!slabs || slabs.length === 0) {
    slabs = [{ gte: 0, lte: 0, fee: 0 }];
  }
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
      if (parsedArrInd === errorInd) {
        removeError();
      }
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
      if (parsedArrInd === errorInd) {
        removeError();
      }
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
      {slabs.map((item, index) => (
        <div key={index} className={`display-flex${index === errorInd ? ' input-invalid' : ''}`}>
          <div className="display-flex">
            <div className="slabs-input-container">
              <Input
                addonBefore="₹"
                className="slabs-input"
                type="number"
                value={item.gte}
                data-key="gte"
                data-arr-ind={index}
                disabled={index > 0}
                onChange={handleSlabValueChange}
              />
            </div>
            <div className="slabs-input-container">
              <Input
                addonBefore="₹"
                className="slabs-input"
                type="number"
                value={item.lte}
                data-key="lte"
                data-arr-ind={index}
                onChange={handleSlabValueChange}
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
              className="display-flex flex-center pointer"
              data-arr-ind={index}
              onClick={removeSlab}
            >
              {' '}
              X{' '}
            </div>
          ) : null}
        </div>
      ))}
      <div className="add-slabs-cta font-12 font-bold pointer" onClick={addMoreSlabs}>
        + Add More Slabs
      </div>
    </>
  );
};

export default Slabs;
