import { useEffect } from 'react';
import ListFilter from 'merchant/components/ListFilter';
import {
  ATTRIBUTE_TYPE,
  HEADING_SUBTEXT,
} from 'merchant/views/MagicCheckout/MagicIntelligence/constants';

export const EmptyComponent = (onUploadClick, txt, hasNoData) => () =>
  (
    <div className="empty-table-message">
      {hasNoData.current ? (
        <p>No result found!</p>
      ) : (
        <>
          <p>{`No ${txt} Set!`}</p>
          {onUploadClick && (
            <button className="btn btn-primary btn-shine" onClick={onUploadClick}>
              <i className="i i-plus" />
              <span>{`Add New ${txt}`}</span>
            </button>
          )}
        </>
      )}
    </div>
  );

const IntelligenceContainer = (props) => {
  const {
    fetchAll,
    ctaText,
    onUploadClick,
    formName,
    list,
    attributeType,
    setAttributeType,
    attributeValue,
    setAttributeValue,
    resetHandler,
    count,
    setCount,
    skip,
    hasNoData,
  } = props;

  const onSubmitHandler = () => {
    hasNoData.current = true;
    skip.current = 0;
    if (!fetchAll) return;
    fetchAll({
      attribute_type: attributeType,
      attribute_value: attributeType === '' ? '' : attributeValue,
      count,
      skip: 0,
    });
  };

  useEffect(() => {
    setAttributeValue('');
  }, [attributeType]);

  return (
    <>
      <div className="content-wrapper">
        <div className="row list-header">
          <div className="col-md-10 d-flex p--0">
            <label>{ctaText}</label>
            <p className="list-header-subText">{HEADING_SUBTEXT[ctaText]}</p>
          </div>
          <div className="col-md-2 p--0">
            <span className="cta-container pull-right">
              <button type="button" className="btn btn-primary btn-shine" onClick={onUploadClick}>
                <i className="i i-plus" />
                <span>{`Add New ${ctaText}`}</span>
              </button>
            </span>
          </div>
        </div>
      </div>
      <ListFilter
        form={`${formName}-form`}
        onSubmit={onSubmitHandler}
        count={count}
        resetHandler={resetHandler}
      >
        <div className="form-group list-filter-item">
          <label>Type</label>
          <div className="select-control">
            <select
              className="form-control input-sm"
              value={attributeType}
              onChange={(e) => setAttributeType(e.target.value)}
              name="type"
            >
              <option value="">All</option>
              {list.map((item, index) => (
                <option value={item} key={index}>
                  {ATTRIBUTE_TYPE[item]}
                </option>
              ))}
            </select>
          </div>
        </div>

        {attributeType === '' ? null : (
          <div className="form-group list-filter-item">
            <label>Value</label>
            <input
              type="text"
              className="form-control input-sm"
              value={attributeValue}
              onChange={(e) => setAttributeValue(e.target.value)}
              name="value"
            />
          </div>
        )}
        <div className="form-group list-filter-item count">
          <label>Count</label>
          <input
            min={1}
            max={100}
            type="number"
            className="form-control input-sm"
            value={count}
            onChange={(e) => setCount(e.target.value)}
            name="count"
          />
        </div>
      </ListFilter>
    </>
  );
};

export default IntelligenceContainer;
