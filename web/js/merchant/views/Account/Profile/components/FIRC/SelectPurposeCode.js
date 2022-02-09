import React, { useContext } from 'react';
import Spinner from 'common/ui/Spinner';
import Button from 'common/new-ui/Button';
import Accordion, {
  AccordionItem,
  AccordionItemTitle,
  AccordionItemContent,
} from 'common/ui/Accordion';
import FIRCFormContext from './FIRCFormContext';

const SelectPurposeCode = (props) => {
  const { formState, handleNext } = useContext(FIRCFormContext);
  const { search, onSearch, clearSearch, onSelect, purposeCodeList } = props;

  if (purposeCodeList.isLoading) {
    return (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="purpose-code-container">
      <div className="header-section content-box">
        <div className="search-container">
          <i className="i i-search" />
          <input
            type="text"
            placeholder="Search code or Industry"
            name="search_code"
            className="form-control purpose-code-search-input"
            autoFocus={true}
            value={search.text}
            onChange={onSearch}
          />
          <Button.Transparent className="close-icon" onClick={clearSearch}>
            <i className="i i-close" />
          </Button.Transparent>
        </div>
      </div>

      <div className="scroll-container">
        {search.text &&
          (search.results?.length > 0 ? (
            <div className="list-container">
              {search.results.map((code) => (
                <div key={code.purposeCode} className="list-item" onClick={() => onSelect(code)}>
                  <p>{code.purposeCode}</p>
                  <div className="pr-10">{code.description}</div>
                  {formState.purpose_code === code.purposeCode && (
                    <i className="i i-tick tick-icon" />
                  )}
                </div>
              ))}
            </div>
          ) : (
            <div className="content-box no-results">
              No results found for &quot;<b>{search.text}</b>&quot;
            </div>
          ))}

        <div className="collapse-section">
          <Accordion>
            {purposeCodeList.data?.length > 0 &&
              purposeCodeList.data.map((categoryCodes) => (
                <AccordionItem key={categoryCodes.purposeGroup}>
                  <AccordionItemTitle>{categoryCodes.purposeGroup}</AccordionItemTitle>
                  <AccordionItemContent>
                    <div className="list-container">
                      {categoryCodes.codes.map((code) => (
                        <div
                          key={code.purposeCode}
                          className="list-item"
                          onClick={() => onSelect(code)}
                        >
                          <p className="fw-600">{code.purposeCode}</p>
                          <div className="pr-10">{code.description}</div>
                          {formState.purpose_code === code.purposeCode && (
                            <i className="i i-tick tick-icon" />
                          )}
                        </div>
                      ))}
                    </div>
                  </AccordionItemContent>
                </AccordionItem>
              ))}
          </Accordion>
        </div>
      </div>

      <div className="footer-section">
        <Button.Primary
          className="btn-block"
          disabled={formState.purpose_code === ''}
          onClick={handleNext}
        >
          Next
        </Button.Primary>
      </div>
    </div>
  );
};

export default React.memo(SelectPurposeCode);
